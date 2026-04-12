<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LoginChallenge;
use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use App\Notifications\TwoFactorRecoveryCodeNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Support\TwoFactorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactor)
    {
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        LoginChallenge::query()->where('user_id', $user->id)->delete();

        $token = Str::random(80);
        $challenge = LoginChallenge::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
            'mode' => $user->two_factor_enabled ? 'challenge' : 'setup',
            'two_factor_secret' => $user->two_factor_enabled ? null : $this->twoFactor->generateSecret(),
            'expires_at' => now()->addMinutes(10),
        ]);

        if (! $user->two_factor_enabled) {
            return response()->json([
                'message' => 'Two-factor authentication setup is required before signing in.',
                'requires_two_factor_setup' => true,
                'challenge_token' => $token,
                'expires_at' => $challenge->expires_at,
                'setup' => [
                    'secret' => $challenge->two_factor_secret,
                    'otpauth_url' => $this->twoFactor->provisioningUri(
                        config('app.name', 'Premax Admin'),
                        $user->email,
                        $challenge->two_factor_secret
                    ),
                ],
                'user' => [
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Two-factor authentication code required.',
            'requires_two_factor' => true,
            'challenge_token' => $token,
            'expires_at' => $challenge->expires_at,
            'user' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
        ]);
    }

    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'code' => 'required|string|min:6|max:8',
        ]);

        $challenge = LoginChallenge::query()
            ->valid()
            ->with('user.roles.permissions', 'user.directPermissions')
            ->where('token_hash', hash('sha256', $request->string('challenge_token')))
            ->latest()
            ->first();

        if (! $challenge) {
            return response()->json([
                'message' => 'This login session has expired. Please sign in again.',
            ], 422);
        }

        $user = $challenge->user;
        $secret = $challenge->mode === 'setup'
            ? $challenge->two_factor_secret
            : ($user->two_factor_secret ? Crypt::decryptString($user->two_factor_secret) : null);

        if (! $secret || ! $this->twoFactor->verifyCode($secret, $request->string('code'))) {
            return response()->json([
                'message' => 'The authentication code is invalid.',
            ], 422);
        }

        if ($challenge->mode === 'setup') {
            $user->forceFill([
                'two_factor_secret' => Crypt::encryptString($secret),
                'two_factor_confirmed_at' => now(),
            ])->save();
        }

        LoginChallenge::query()->where('user_id', $user->id)->delete();

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('admin-spa', ['*'], now()->addHours(8))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if ($user) {
            $plainToken = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($plainToken),
                    'created_at' => now(),
                ]
            );

            $user->notify(new AdminPasswordResetNotification(
                $this->websiteUrl('/login?email='.urlencode($user->email).'&reset_token='.urlencode($plainToken))
            ));
        }

        return response()->json([
            'message' => 'If that email address exists, a reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

        if (! $record || ! Hash::check($request->token, $record->token) || Carbon::parse($record->created_at)->addMinutes(60)->isPast()) {
            return response()->json(['message' => 'This password reset link is invalid or expired.'], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json(['message' => 'Unable to reset password for this user.'], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
        ])->save();

        $user->tokens()->delete();
        LoginChallenge::query()->where('user_id', $user->id)->delete();
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password reset successful. You can now sign in.']);
    }

    public function requestTwoFactorRecovery(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return response()->json(['message' => 'If that account exists, a recovery code has been sent.']);
        }

        LoginChallenge::query()->where('user_id', $user->id)->delete();

        $plainToken = Str::random(80);
        $code = (string) random_int(100000, 999999);

        LoginChallenge::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $plainToken),
            'verification_code_hash' => hash('sha256', $code),
            'mode' => 'recovery_verify',
            'expires_at' => now()->addMinutes(10),
        ]);

        $user->notify(new TwoFactorRecoveryCodeNotification($code));

        return response()->json([
            'message' => 'If that account exists, a recovery code has been sent.',
            'challenge_token' => $plainToken,
        ]);
    }

    public function verifyTwoFactorRecovery(Request $request)
    {
        $request->validate([
            'challenge_token' => 'required|string',
            'code' => 'required|string|size:6',
        ]);

        $challenge = LoginChallenge::query()
            ->valid()
            ->with('user')
            ->where('token_hash', hash('sha256', $request->challenge_token))
            ->where('mode', 'recovery_verify')
            ->latest()
            ->first();

        if (! $challenge || ! hash_equals($challenge->verification_code_hash ?? '', hash('sha256', $request->code))) {
            return response()->json(['message' => 'The recovery code is invalid or expired.'], 422);
        }

        $user = $challenge->user;
        LoginChallenge::query()->where('user_id', $user->id)->delete();

        $newToken = Str::random(80);
        $setupChallenge = LoginChallenge::create([
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $newToken),
            'mode' => 'setup',
            'two_factor_secret' => $this->twoFactor->generateSecret(),
            'expires_at' => now()->addMinutes(10),
        ]);

        return response()->json([
            'message' => 'Recovery code verified. Set up your new two-factor authentication now.',
            'challenge_token' => $newToken,
            'setup' => [
                'secret' => $setupChallenge->two_factor_secret,
                'otpauth_url' => $this->twoFactor->provisioningUri(
                    config('app.name', 'Premax Admin'),
                    $user->email,
                    $setupChallenge->two_factor_secret
                ),
            ],
            'user' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
         if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user->loadMissing('roles.permissions', 'directPermissions');

        return response()->json($this->userPayload($user));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    private function userPayload(User $user): array
    {
        $permissions = $user->effectivePermissions();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'roles' => $user->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ])->values(),
            'permissions' => $permissions->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'group_name' => $permission->group_name,
            ])->values(),
            'permission_slugs' => $permissions->pluck('slug')->values(),
            'initials' => $user->initials,
            'avatar' => $user->avatar_url ?? null,
            'two_factor_enabled' => $user->two_factor_enabled,
        ];
    }

    private function websiteUrl(string $path = ''): string
    {
        $base = rtrim(env('APP_URL'), '/');
        return $base.'/'.ltrim($path, '/');
    }
}
