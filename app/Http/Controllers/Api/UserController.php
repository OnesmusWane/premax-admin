<?php

namespace App\Http\Controllers\Api;
 
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AdminPasswordResetNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
 
class UserController extends Controller
{
    public function meta()
    {
        return response()->json([
            'roles' => Role::query()
                ->with('permissions:id,name,slug,group_name')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'description' => $role->description,
                    'permissions' => $role->permissions->map(fn ($permission) => [
                        'id' => $permission->id,
                        'slug' => $permission->slug,
                    ])->values(),
                ])
                ->values(),
            'permissions' => Permission::query()
                ->orderBy('group_name')
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'group_name', 'description']),
        ]);
    }

    public function index()
    {
        return response()->json(
            User::query()
                ->with(['roles:id,name,slug', 'directPermissions:id,name,slug,group_name', 'roles.permissions:id,name,slug,group_name'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $user) => $this->formatUser($user))
                ->values()
        );
    }
 
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:150',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'boolean',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);
 
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => Role::query()->whereKey($request->role_ids[0])->value('slug') ?? 'receptionist',
            'is_active' => $request->boolean('is_active', true),
        ]);

        $user->roles()->sync($request->role_ids);
        $user->directPermissions()->sync($request->permission_ids ?? []);
        $user->load(['roles.permissions', 'directPermissions']);
 
        return response()->json($this->formatUser($user), 201);
    }
 
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'     => 'sometimes|string|max:150',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'boolean',
            'role_ids' => 'sometimes|array|min:1',
            'role_ids.*' => 'exists:roles,id',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);
 
        $data = $request->only(['name', 'email', 'is_active']);

        if ($request->has('role_ids') && count($request->role_ids)) {
            $data['role'] = Role::query()->whereKey($request->role_ids[0])->value('slug') ?? $user->role;
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
 
        $user->update($data);

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        if ($request->has('permission_ids')) {
            $user->directPermissions()->sync($request->permission_ids ?? []);
        }

        if ($request->has('is_active') && ! $request->boolean('is_active')) {
            $user->tokens()->delete();
        }
 
        return response()->json(
            $this->formatUser($user->fresh()->load(['roles.permissions', 'directPermissions']))
        );
    }
 
    public function destroy(User $user)
    {
        // Prevent deleting self
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account.'], 422);
        }
 
        $user->delete();
        return response()->json(['message' => 'User deleted.']);
    }

    public function resetTwoFactor(User $user)
    {
        if ($user->id === request()->user()->id) {
            return response()->json(['message' => 'Use another admin account to reset your own 2FA.'], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        \App\Models\LoginChallenge::query()->where('user_id', $user->id)->delete();
        $user->tokens()->delete();

        return response()->json(['message' => 'Two-factor authentication reset. The user must set it up again on next login.']);
    }

    public function sendPasswordResetLink(User $user)
    {
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

        return response()->json(['message' => 'Password reset link sent to the user email.']);
    }

    private function formatUser(User $user): array
    {
        $effectivePermissions = $user->effectivePermissions();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'created_at' => $user->created_at,
            'roles' => $user->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
            ])->values(),
            'direct_permissions' => $user->directPermissions->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'group_name' => $permission->group_name,
            ])->values(),
            'permission_slugs' => $effectivePermissions->pluck('slug')->values(),
            'two_factor_enabled' => $user->two_factor_enabled,
        ];
    }

    private function websiteUrl(string $path = ''): string
    {
        $base = rtrim(env('WEBSITE_URL', config('app.url', 'http://localhost:8006')), '/');
        return $base.'/'.ltrim($path, '/');
    }
}
 
