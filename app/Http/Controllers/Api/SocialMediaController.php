<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PublishSocialPostJob;
use App\Models\ContactInformation;
use App\Models\SocialAccount;
use App\Models\SocialComment;
use App\Models\SocialConversation;
use App\Models\SocialMessage;
use App\Models\SocialPost;
use App\Services\Social\FacebookConnector;
use App\Services\Social\InstagramConnector;
use App\Services\Social\TikTokConnector;
use App\Support\SocialConnectorRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SocialMediaController extends Controller
{
    public function index()
    {
        $accounts = SocialAccount::query()
            ->withCount(['comments', 'conversations', 'postTargets'])
            ->orderByRaw("
                CASE platform
                    WHEN 'tiktok' THEN 1
                    WHEN 'facebook' THEN 2
                    WHEN 'instagram' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('name')
            ->get();

        $posts = SocialPost::query()
            ->with(['creator:id,name', 'targets.account:id,name,platform,username'])
            ->withCount('comments')
            ->latest()
            ->limit(24)
            ->get();

        $comments = SocialComment::query()
            ->with(['account:id,name,platform,username', 'post:id,title,status', 'assignee:id,name'])
            ->latest('received_at')
            ->limit(20)
            ->get();

        $conversations = SocialConversation::query()
            ->with([
                'account:id,name,platform,username,profile_image_url',
                'messages' => fn ($query) => $query->orderByDesc('sent_at')->orderByDesc('created_at')->limit(30),
            ])
            ->orderByDesc('unread_count')
            ->orderByDesc('last_message_at')
            ->limit(30)
            ->get();

        $contact = ContactInformation::primary()->first();

        return response()->json([
            'whatsapp_number' => $contact?->phone_whatsapp,
            'website_url' => $contact?->website_url ?: 'https://premaxautoservice.co.ke/',
            'summary' => [
                'connected_accounts' => $accounts->where('auth_status', 'connected')->count(),
                'scheduled_posts' => SocialPost::query()->where('status', 'scheduled')->count(),
                'published_this_week' => SocialPost::query()
                    ->where('status', 'published')
                    ->where('published_at', '>=', now()->subDays(7))
                    ->count(),
                'open_conversations' => SocialConversation::query()->where('status', 'open')->count(),
                'unread_messages' => SocialConversation::query()->sum('unread_count'),
                'needs_reply_comments' => SocialComment::query()->where('status', 'needs_reply')->count(),
            ],
            'platforms' => [
                'tiktok' => $this->platformSnapshot('tiktok', $accounts, $posts, $conversations),
                'facebook' => $this->platformSnapshot('facebook', $accounts, $posts, $conversations),
                'instagram' => $this->platformSnapshot('instagram', $accounts, $posts, $conversations),
            ],
            'connection_blueprints' => SocialConnectorRegistry::all(),
            'accounts' => $accounts->map(fn (SocialAccount $account) => $this->serializeAccount($account)),
            'posts' => $posts->map(fn (SocialPost $post) => $this->serializePost($post)),
            'comments' => $comments,
            'conversations' => $conversations,
            'recent_activity' => $this->recentActivity($posts, $conversations, $comments),
        ]);
    }

    public function storeAccount(Request $request)
    {
        [$credentialRule, $credentialFieldRules] = SocialConnectorRegistry::credentialRules($request->input('platform', ''));

        $data = $request->validate(array_merge([
            'platform' => ['required', Rule::in(['facebook', 'instagram', 'tiktok'])],
            'name' => 'required|string|max:120',
            'username' => 'nullable|string|max:120',
            'external_account_id' => 'nullable|string|max:190',
            'connection_type' => ['nullable', Rule::in(['oauth', 'token', 'manual'])],
            'auth_status' => ['nullable', Rule::in(['pending', 'connected', 'disconnected', 'expired', 'error'])],
            'status' => ['nullable', Rule::in(['active', 'paused', 'attention'])],
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string|max:80',
            'profile_image_url' => 'nullable|url|max:2048',
            'followers_count' => 'nullable|integer|min:0',
            'inbox_count' => 'nullable|integer|min:0',
            'credentials' => $credentialRule,
            'auto_sync_enabled' => 'nullable|boolean',
            'sync_frequency_minutes' => 'nullable|integer|min:5|max:1440',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ], $credentialFieldRules));

        if (SocialAccount::where('platform', $data['platform'])->exists()) {
            throw ValidationException::withMessages([
                'platform' => $this->platformLabel($data['platform']).' already has a connected account. Remove the existing account before connecting a new one.',
            ]);
        }

        $account = SocialAccount::create([
            ...$data,
            'connection_type' => $data['connection_type'] ?? SocialConnectorRegistry::for($data['platform'])['connection_type'],
            'auth_status' => 'pending',
            'status' => 'attention',
            'capabilities' => $data['capabilities'] ?? $this->defaultCapabilities($data['platform']),
            'followers_count' => $data['followers_count'] ?? 0,
            'inbox_count' => $data['inbox_count'] ?? 0,
            'credentials' => $data['credentials'] ?? [],
            'auto_sync_enabled' => $data['auto_sync_enabled'] ?? true,
            'sync_frequency_minutes' => $data['sync_frequency_minutes'] ?? 15,
            'sync_status' => 'synced',
            'is_active' => false,
            'webhook_verify_token' => Str::random(40),
            'connected_by' => $request->user()?->id,
            'last_synced_at' => now(),
            'last_sync_started_at' => now(),
            'last_sync_completed_at' => now(),
        ]);

        // Immediately validate any token that was supplied so the status reflects reality
        $this->validateAndUpdateAccountToken($account->fresh());

        return response()->json($this->serializeAccount($account->fresh()), 201);
    }

    public function updateAccount(Request $request, SocialAccount $socialAccount)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'username' => 'nullable|string|max:120',
            'external_account_id' => 'nullable|string|max:190',
            'connection_type' => ['nullable', Rule::in(['oauth', 'token', 'manual'])],
            'auth_status' => ['nullable', Rule::in(['pending', 'connected', 'disconnected', 'expired', 'error'])],
            'status' => ['nullable', Rule::in(['active', 'paused', 'attention'])],
            'capabilities' => 'nullable|array',
            'capabilities.*' => 'string|max:80',
            'profile_image_url' => 'nullable|url|max:2048',
            'followers_count' => 'nullable|integer|min:0',
            'inbox_count' => 'nullable|integer|min:0',
            'credentials' => 'nullable|array',
            'credentials.*' => 'nullable|string|max:5000',
            'auto_sync_enabled' => 'nullable|boolean',
            'sync_frequency_minutes' => 'nullable|integer|min:5|max:1440',
            'metadata' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        if (array_key_exists('auth_status', $data) && $data['auth_status'] === 'disconnected') {
            $data['is_active'] = false;
            $data['status'] = 'paused';
        }

        if (array_key_exists('auth_status', $data) && $data['auth_status'] === 'connected') {
            $data['is_active'] = $data['is_active'] ?? true;
            $data['status'] = $data['status'] ?? 'active';
            $data['sync_error'] = null;
        }

        if ($request->hasAny(['auth_status', 'status', 'is_active', 'followers_count', 'inbox_count'])) {
            $data['last_synced_at'] = now();
        }

        if (array_key_exists('credentials', $data)) {
            $data['credentials'] = SocialConnectorRegistry::mergeCredentials(
                $socialAccount->platform,
                $socialAccount->credentials ?? [],
                $data['credentials'] ?? [],
            );

            $missing = collect(SocialConnectorRegistry::requiredCredentialKeys($socialAccount->platform))
                ->reject(fn (string $key) => filled(data_get($data['credentials'], $key)))
                ->values();

            if ($missing->isNotEmpty()) {
                throw ValidationException::withMessages(
                    $missing->mapWithKeys(fn (string $key) => ["credentials.{$key}" => "The {$key} field is required."])->all()
                );
            }
        }

        $socialAccount->update($data);

        // Re-validate token whenever credentials are updated
        if (array_key_exists('credentials', $data)) {
            $this->validateAndUpdateAccountToken($socialAccount->fresh());
        }

        return response()->json($this->serializeAccount($socialAccount->fresh()));
    }

    public function syncAccount(SocialAccount $socialAccount)
    {
        $socialAccount->update([
            'sync_status' => 'syncing',
            'sync_error' => null,
            'last_sync_started_at' => now(),
        ]);

        try {
            if ($socialAccount->platform === 'facebook') {
                $connector = SocialConnectorRegistry::make($socialAccount);

                if ($connector instanceof FacebookConnector) {
                    $inspection = $connector->inspectToken();
                    $expiresAt = $inspection['expires_at'] ?? null;

                    if (! ($inspection['checked'] ?? false) || ! ($inspection['is_valid'] ?? false)) {
                        $this->markAccountExpired(
                            $socialAccount,
                            $inspection['message'] ?? 'Facebook token is no longer valid. Reconnect the account.',
                            $expiresAt,
                        );

                        return response()->json($this->serializeAccount($socialAccount->fresh()));
                    }

                    $socialAccount->update([
                        'auth_status' => 'connected',
                        'status' => 'active',
                        'is_active' => true,
                        'sync_status' => 'synced',
                        'sync_error' => null,
                        'token_expires_at' => $expiresAt,
                        'last_token_checked_at' => now(),
                        'last_synced_at' => now(),
                        'last_sync_completed_at' => now(),
                    ]);

                    return response()->json($this->serializeAccount($socialAccount->fresh()));
                }
            }

            $socialAccount->update([
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'last_sync_completed_at' => now(),
                'status' => $socialAccount->auth_status === 'connected' ? 'active' : $socialAccount->status,
            ]);
        } catch (\Throwable $e) {
            if ($socialAccount->platform === 'facebook' && FacebookConnector::isExpiredTokenException($e)) {
                $this->markAccountExpired($socialAccount, $e->getMessage());

                return response()->json($this->serializeAccount($socialAccount->fresh()));
            }

            $socialAccount->update([
                'sync_status' => 'error',
                'sync_error' => mb_substr($e->getMessage(), 0, 500),
                'last_sync_completed_at' => now(),
            ]);

            throw ValidationException::withMessages([
                'account' => [$e->getMessage()],
            ]);
        }

        return response()->json($this->serializeAccount($socialAccount->fresh()));
    }

    public function refreshToken(SocialAccount $socialAccount)
    {
        if ($socialAccount->platform !== 'facebook') {
            throw ValidationException::withMessages([
                'platform' => ['Token refresh is only supported for Facebook accounts.'],
            ]);
        }

        $creds = $socialAccount->credentials ?? [];
        $canRefresh = filled($creds['app_id'] ?? '')
            && filled($creds['app_secret'] ?? '')
            && filled($creds['user_access_token'] ?? '');

        if (! $canRefresh) {
            throw ValidationException::withMessages([
                'credentials' => ['app_id, app_secret, and user_access_token must all be set to refresh the token.'],
            ]);
        }

        $refreshed = FacebookConnector::tryRefreshAccount($socialAccount);

        if (! $refreshed) {
            throw ValidationException::withMessages([
                'token' => ['Token refresh failed. The current token may be fully expired — update the user_access_token credential and try again.'],
            ]);
        }

        $this->syncInstagramCredentials($socialAccount);

        return response()->json($this->serializeAccount($socialAccount->fresh()));
    }

    public function getOAuthRedirectUrl(SocialAccount $socialAccount)
    {
        if ($socialAccount->platform !== 'facebook') {
            throw ValidationException::withMessages([
                'platform' => ['OAuth is only supported for Facebook accounts.'],
            ]);
        }

        $creds = $socialAccount->credentials ?? [];

        if (! filled($creds['app_id'] ?? '') || ! filled($creds['app_secret'] ?? '')) {
            throw ValidationException::withMessages([
                'credentials' => ['app_id and app_secret must be saved before starting the OAuth flow.'],
            ]);
        }

        // Fixed callback URL — register exactly this ONE URL in Meta Developer Portal
        $redirectUri = url('/api/social-media/oauth/facebook/callback');

        // Account ID travels in the encrypted state so the callback knows which account to update
        $state     = encrypt($socialAccount->id . '|' . now()->timestamp);
        $connector = new FacebookConnector($creds);

        return response()->json([
            'oauth_url'    => $connector->getOAuthUrl($redirectUri, $state),
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * Fixed-URL Facebook OAuth callback — no auth middleware, no account ID in the path.
     * Facebook redirects here after the user approves the dialog.
     * Register ONLY this URL in Meta Developer Portal → Facebook Login → Valid OAuth Redirect URIs:
     *   https://admin.premaxautoservice.co.ke/api/social-media/oauth/facebook/callback
     */
    public function oauthCallback(Request $request)
    {
        $frontendBase = rtrim(config('app.url'), '/');
        $redirectUri  = url('/api/social-media/oauth/facebook/callback');

        if ($request->has('error')) {
            Log::warning('Facebook OAuth denied by user', [
                'error'       => $request->input('error'),
                'description' => $request->input('error_description'),
            ]);

            return redirect($frontendBase . '/social-media?oauth=denied');
        }

        $code  = $request->string('code')->toString();
        $state = $request->string('state')->toString();

        if (! filled($code) || ! filled($state)) {
            return redirect($frontendBase . '/social-media?oauth=error&reason=missing_params');
        }

        // Recover the account ID from the encrypted state we built in getOAuthRedirectUrl
        try {
            $decrypted = decrypt($state);
            $accountId = (int) explode('|', $decrypted)[0];
        } catch (\Throwable) {
            Log::error('Facebook OAuth: state decryption failed');

            return redirect($frontendBase . '/social-media?oauth=error&reason=invalid_state');
        }

        $socialAccount = SocialAccount::find($accountId);

        if (! $socialAccount || $socialAccount->platform !== 'facebook') {
            Log::error('Facebook OAuth: account not found', ['account_id' => $accountId]);

            return redirect($frontendBase . '/social-media?oauth=error&reason=account_not_found');
        }

        try {
            $creds     = $socialAccount->credentials ?? [];
            $connector = new FacebookConnector($creds);
            $tokens    = $connector->exchangeCodeForTokens($code, $redirectUri);

            $expiresAt = ($tokens['expires_in'] ?? 0) > 0
                ? now()->addSeconds($tokens['expires_in'])
                : null;

            $socialAccount->update([
                'credentials' => array_merge($creds, array_filter([
                    'user_access_token' => $tokens['user_access_token'],
                    'page_access_token' => $tokens['page_access_token'],
                ], fn ($v) => $v !== null)),
                'auth_status'            => 'connected',
                'status'                 => 'active',
                'is_active'              => true,
                'sync_status'            => 'synced',
                'sync_error'             => null,
                'token_expires_at'       => $expiresAt,
                'last_token_checked_at'  => now(),
                'last_synced_at'         => now(),
                'last_sync_started_at'   => now(),
                'last_sync_completed_at' => now(),
            ]);

            $this->syncInstagramCredentials($socialAccount);

            Log::info('Facebook OAuth completed — tokens stored', [
                'account_id'         => $socialAccount->id,
                'page_token_fetched' => filled($tokens['page_access_token']),
                'expires_at'         => $expiresAt?->toIso8601String(),
            ]);

            return redirect($frontendBase . '/social-media?oauth=success');
        } catch (\Throwable $e) {
            Log::error('Facebook OAuth callback failed', [
                'account_id' => $socialAccount->id,
                'error'      => $e->getMessage(),
            ]);

            $socialAccount->update([
                'auth_status' => 'error',
                'sync_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);

            return redirect($frontendBase . '/social-media?oauth=error');
        }
    }

    public function regeneratePageToken(SocialAccount $socialAccount)
    {
        if ($socialAccount->platform !== 'facebook') {
            throw ValidationException::withMessages([
                'platform' => ['Page token regeneration is only supported for Facebook accounts.'],
            ]);
        }

        try {
            $connector = new FacebookConnector($socialAccount->credentials ?? []);
            $pageToken = $connector->regeneratePageToken();

            $socialAccount->update([
                'credentials' => array_merge($socialAccount->credentials ?? [], [
                    'page_access_token' => $pageToken,
                ]),
                'auth_status'    => 'connected',
                'status'         => 'active',
                'sync_error'     => null,
                'last_synced_at' => now(),
            ]);

            $this->syncInstagramCredentials($socialAccount);

            return response()->json($this->serializeAccount($socialAccount->fresh()));
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'token' => [$e->getMessage()],
            ]);
        }
    }

    public function getTikTokOAuthUrl(SocialAccount $socialAccount)
    {
        if ($socialAccount->platform !== 'tiktok') {
            throw ValidationException::withMessages([
                'platform' => ['OAuth URL generation is only supported for TikTok accounts.'],
            ]);
        }

        $creds = $socialAccount->credentials ?? [];

        if (! filled($creds['client_key'] ?? '')) {
            throw ValidationException::withMessages([
                'credentials' => ['client_key must be saved before starting the TikTok OAuth flow.'],
            ]);
        }

        $redirectUri = url("/api/social-media/oauth/tiktok/{$socialAccount->id}/callback");
        $state       = encrypt($socialAccount->id . '|' . now()->timestamp);
        $connector   = new TikTokConnector($creds);

        return response()->json([
            'oauth_url'    => $connector->getOAuthUrl($redirectUri, $state),
            'redirect_uri' => $redirectUri,
        ]);
    }

    /**
     * Per-account TikTok OAuth callback — no auth middleware, account ID in the path.
     * TikTok redirects here after the user approves the dialog.
     * Register this URL (per account) in TikTok Developer Portal → your app → Redirect URI:
     *   https://admin.premaxautoservice.co.ke/api/social-media/oauth/tiktok/{id}/callback
     */
    public function tikTokOAuthCallback(Request $request, SocialAccount $socialAccount)
    {
        $frontendBase = rtrim(config('app.url'), '/');
        $redirectUri  = url("/api/social-media/oauth/tiktok/{$socialAccount->id}/callback");

        if ($socialAccount->platform !== 'tiktok') {
            return redirect($frontendBase . '/social-media?oauth=error&platform=tiktok&reason=account_not_found');
        }

        if ($request->has('error')) {
            Log::warning('TikTok OAuth denied by user', [
                'error'       => $request->input('error'),
                'description' => $request->input('error_description'),
                'account_id'  => $socialAccount->id,
            ]);

            return redirect($frontendBase . '/social-media?oauth=denied&platform=tiktok');
        }

        $code  = $request->string('code')->toString();
        $state = $request->string('state')->toString();

        if (! filled($code) || ! filled($state)) {
            return redirect($frontendBase . '/social-media?oauth=error&platform=tiktok&reason=missing_params');
        }

        // Verify state was signed by us (CSRF guard) — account ID is already in the route
        try {
            decrypt($state);
        } catch (\Throwable) {
            Log::error('TikTok OAuth: state verification failed', ['account_id' => $socialAccount->id]);

            return redirect($frontendBase . '/social-media?oauth=error&platform=tiktok&reason=invalid_state');
        }

        try {
            $creds     = $socialAccount->credentials ?? [];
            $connector = new TikTokConnector($creds);
            $tokens    = $connector->exchangeCodeForTokens($code, $redirectUri);

            $expiresIn = (int) ($tokens['expires_in'] ?? 86400);
            $expiresAt = now()->addSeconds($expiresIn);

            $socialAccount->update([
                'credentials' => array_merge($creds, array_filter([
                    'open_id'       => $tokens['open_id'] ?? null,
                    'access_token'  => $tokens['access_token'] ?? null,
                    'refresh_token' => $tokens['refresh_token'] ?? null,
                ], fn ($v) => $v !== null)),
                'auth_status'            => 'connected',
                'status'                 => 'active',
                'is_active'              => true,
                'sync_status'            => 'synced',
                'sync_error'             => null,
                'token_expires_at'       => $expiresAt,
                'last_token_checked_at'  => now(),
                'last_synced_at'         => now(),
                'last_sync_started_at'   => now(),
                'last_sync_completed_at' => now(),
            ]);

            // Pull profile info to populate name, avatar, and follower count
            try {
                $freshCreds = $socialAccount->fresh()->credentials ?? [];
                $userInfo   = (new TikTokConnector($freshCreds))->getUserInfo();

                if (! empty($userInfo)) {
                    $socialAccount->update([
                        'external_account_id' => $userInfo['open_id'] ?? $socialAccount->external_account_id,
                        'username'            => $userInfo['display_name'] ?? $socialAccount->username,
                        'profile_image_url'   => $userInfo['avatar_url'] ?? $socialAccount->profile_image_url,
                        'followers_count'     => (int) ($userInfo['follower_count'] ?? $socialAccount->followers_count),
                    ]);
                }
            } catch (\Throwable $profileException) {
                Log::warning('TikTok OAuth: user info fetch failed (non-fatal)', [
                    'account_id' => $socialAccount->id,
                    'error'      => $profileException->getMessage(),
                ]);
            }

            Log::info('TikTok OAuth completed — tokens stored', [
                'account_id' => $socialAccount->id,
                'open_id'    => $tokens['open_id'] ?? null,
                'expires_at' => $expiresAt->toIso8601String(),
            ]);

            return redirect($frontendBase . '/social-media?oauth=success&platform=tiktok');
        } catch (\Throwable $e) {
            Log::error('TikTok OAuth callback failed', [
                'account_id' => $socialAccount->id,
                'error'      => $e->getMessage(),
            ]);

            $socialAccount->update([
                'auth_status' => 'error',
                'sync_error'  => mb_substr($e->getMessage(), 0, 500),
            ]);

            return redirect($frontendBase . '/social-media?oauth=error&platform=tiktok');
        }
    }

    public function refreshTikTokToken(SocialAccount $socialAccount)
    {
        if ($socialAccount->platform !== 'tiktok') {
            throw ValidationException::withMessages([
                'platform' => ['Token refresh is only supported for TikTok accounts.'],
            ]);
        }

        $creds = $socialAccount->credentials ?? [];
        $canRefresh = filled($creds['client_key'] ?? '')
            && filled($creds['client_secret'] ?? '')
            && filled($creds['refresh_token'] ?? '');

        if (! $canRefresh) {
            throw ValidationException::withMessages([
                'credentials' => ['client_key, client_secret, and refresh_token must all be set to refresh the TikTok token.'],
            ]);
        }

        $refreshed = TikTokConnector::tryRefreshAccount($socialAccount);

        if (! $refreshed) {
            throw ValidationException::withMessages([
                'token' => ['Token refresh failed. The refresh_token may be expired — re-authorize the account via TikTok OAuth.'],
            ]);
        }

        return response()->json($this->serializeAccount($socialAccount->fresh()));
    }

    /**
     * Sync all Facebook-shared credentials to the linked Instagram account.
     * Called whenever FB tokens are refreshed or re-authorized so IG stays in sync.
     * The only field NOT synced is business_account_id — that belongs solely to IG.
     */
    private function syncInstagramCredentials(SocialAccount $fbAccount): void
    {
        $fbCreds = $fbAccount->fresh()->credentials ?? [];
        $pageId  = $fbCreds['page_id'] ?? null;

        // Find the linked IG account by page_id first, then any IG account as fallback
        $igAccount = null;

        if (filled($pageId)) {
            $igAccount = SocialAccount::where('platform', 'instagram')
                ->whereRaw('JSON_VALID(`credentials`) = 1')
                ->where('credentials->page_id', $pageId)
                ->first();
        }

        if (! $igAccount) {
            $igAccount = SocialAccount::where('platform', 'instagram')->first();
        }

        if (! $igAccount) {
            return;
        }

        $sharedFields = array_filter([
            'app_id'       => $fbCreds['app_id'] ?? null,
            'app_secret'   => $fbCreds['app_secret'] ?? null,
            'access_token' => $fbCreds['page_access_token'] ?? null,
            'page_id'      => $pageId,
        ], fn ($v) => filled($v));

        if (empty($sharedFields)) {
            return;
        }

        $igAccount->update([
            'credentials' => array_merge($igAccount->credentials ?? [], $sharedFields),
        ]);

        Log::info('Instagram credentials synced from Facebook', [
            'fb_account_id' => $fbAccount->id,
            'ig_account_id' => $igAccount->id,
            'fields_synced' => array_keys($sharedFields),
        ]);
    }

    public function webhookCallback(Request $request, string $platform, SocialAccount $socialAccount)
    {
        abort_unless($socialAccount->platform === $platform, 404);

        $providedToken = $request->query('verify_token')
            ?: $request->header('X-Webhook-Token')
            ?: $request->input('verify_token')
            ?: $request->query('hub_verify_token');

        abort_unless(
            filled($socialAccount->webhook_verify_token) && hash_equals($socialAccount->webhook_verify_token, (string) $providedToken),
            403,
            'Invalid webhook verification token.',
        );

        if ($request->has('hub_challenge')) {
            return response($request->query('hub_challenge'), 200);
        }

        $payload = $request->all();
        $metadata = $socialAccount->metadata ?? [];
        $metadata['last_webhook_payload'] = $payload;

        $socialAccount->update([
            'metadata' => $metadata,
            'last_webhook_at' => now(),
            'last_synced_at' => now(),
            'last_sync_started_at' => now(),
            'last_sync_completed_at' => now(),
            'sync_status' => 'synced',
            'sync_error' => null,
            'inbox_count' => $payload['unread_count'] ?? $socialAccount->inbox_count,
        ]);

        return response()->json([
            'message' => 'Webhook received.',
            'account_id' => $socialAccount->id,
        ]);
    }

  public function uploadMedia(Request $request)
{
    $request->validate([
        'file' => 'required|file|mimes:jpg,jpeg,png,mp4,mov|max:51200',
    ]);

    $file      = $request->file('file');
    $cloudName = config('cloudinary.cloud_name');
    $apiKey    = config('cloudinary.api_key');
    $apiSecret = config('cloudinary.api_secret');

    // Log to verify credentials are loading
    Log::info('Cloudinary config', [
        'cloud_name' => $cloudName,
        'api_key'    => $apiKey ? 'set' : 'missing',
        'api_secret' => $apiSecret ? 'set' : 'missing',
    ]);

    if (! filled($cloudName) || ! filled($apiKey) || ! filled($apiSecret)) {
        return response()->json([
            'message' => 'Cloudinary credentials are not configured.',
        ], 500);
    }

    $timestamp = time();

    // Generate Cloudinary signature
    $paramsToSign = "folder=social-media&timestamp={$timestamp}";
    $signature    = sha1($paramsToSign . $apiSecret);

    try {
        $response = \Http::attach(
            'file',
            file_get_contents($file->getRealPath()),
            $file->getClientOriginalName()
        )->post("https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload", [
            'api_key'   => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder'    => 'social-media',
        ]);

        if (! $response->successful()) {
            Log::error('Cloudinary upload failed', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            throw new \RuntimeException(
                'Cloudinary upload failed: ' . ($response->json('error.message') ?? $response->body())
            );
        }

        $url = $response->json('secure_url');

        Log::info('Cloudinary upload success', ['url' => $url]);

        return response()->json([
            'url'       => $url,
            'path'      => $response->json('public_id'),
            'name'      => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
        ], 201);

    } catch (\Throwable $e) {
        Log::error('Cloudinary upload exception', ['error' => $e->getMessage()]);

        return response()->json([
            'message' => 'Media upload failed: ' . $e->getMessage(),
        ], 500);
    }
}

    public function storePost(Request $request)
    {
        $data = $request->validate([
            'title'         => 'required|string|max:150',
            'content'       => 'required|string|max:5000',
            'media'         => 'nullable|array|max:1',
            'media.*'       => 'string|max:2048',
            'link_url'      => 'nullable|url|max:2048',
            'account_ids'   => 'required|array|min:1',
            'account_ids.*' => 'exists:social_accounts,id',
            'publish_now'   => 'nullable|boolean',
            'scheduled_for' => 'nullable|date',
        ]);

        $publishNow = (bool) ($data['publish_now'] ?? false);

        // Must have either publish_now or a scheduled date — no un-dated drafts
        if (! $publishNow && empty($data['scheduled_for'])) {
            throw ValidationException::withMessages([
                'scheduled_for' => ['Select a publish date or choose "Publish immediately".'],
            ]);
        }

        // Instagram requires at least one media file
        $hasInstagram = SocialAccount::whereIn('id', $data['account_ids'])
            ->where('platform', 'instagram')
            ->exists();

        if ($hasInstagram && empty($data['media'])) {
            throw ValidationException::withMessages([
                'media' => ['Instagram requires at least one image or video. Please attach a file.'],
            ]);
        }

        $status        = $publishNow ? 'published' : 'scheduled';
        $targetStatus  = $publishNow ? 'published' : 'scheduled';

        // Create one independent SocialPost per account for clean per-platform management
        $posts = DB::transaction(function () use ($request, $data, $status, $targetStatus, $publishNow) {
            $created = [];

            foreach ($data['account_ids'] as $accountId) {
                $post = SocialPost::create([
                    'title'         => $data['title'],
                    'content'       => $data['content'],
                    'media'         => $data['media'] ?? [],
                    'link_url'      => $data['link_url'] ?? null,
                    'status'        => $status,
                    'scheduled_for' => $publishNow ? null : ($data['scheduled_for'] ?? null),
                    'published_at'  => $publishNow ? now() : null,
                    'created_by'    => $request->user()?->id,
                ]);

                $post->targets()->create([
                    'social_account_id' => $accountId,
                    'status'            => $targetStatus,
                    'published_at'      => $publishNow ? now() : null,
                ]);

                $created[] = $post;
            }

            return $created;
        });

        if ($publishNow) {
            foreach ($posts as $post) {
                $post->targets->each(fn ($target) => PublishSocialPostJob::dispatch($target->id));
            }
        }

        $serialized = collect($posts)->map(
            fn ($post) => $post->fresh()->load(['creator:id,name', 'targets.account:id,name,platform,username'])
        )->all();

        return response()->json(['posts' => $serialized], 201);
    }

    public function updatePost(Request $request, SocialPost $socialPost)
    {
        $data = $request->validate([
            'title' => 'sometimes|nullable|string|max:150',
            'content' => 'sometimes|string|max:5000',
            'media' => 'nullable|array',
            'media.*' => 'string|max:2048',
            'link_url' => 'nullable|url|max:2048',
            'status' => ['nullable', Rule::in(['draft', 'scheduled', 'published', 'failed'])],
            'scheduled_for' => 'nullable|date',
            'account_ids' => 'nullable|array|min:1',
            'account_ids.*' => 'exists:social_accounts,id',
        ]);

        $publishedTargets = $socialPost->targets()
            ->with('account')
            ->whereNotNull('external_post_id')
            ->get();

        if (! empty($data['account_ids']) && $socialPost->status === 'published') {
            throw ValidationException::withMessages([
                'account_ids' => ['Published posts cannot change target accounts. Create a new post for a different platform mix.'],
            ]);
        }

        if (($data['status'] ?? null) === 'published' && ! $socialPost->published_at) {
            $data['published_at'] = now();
        }

        DB::transaction(function () use ($socialPost, $data) {
            $postData = collect($data)->except('account_ids')->all();
            $socialPost->update($postData);

            if (! empty($data['account_ids']) && $socialPost->status !== 'published') {
                $socialPost->targets()->whereNotIn('social_account_id', $data['account_ids'])->delete();

                $existingIds = $socialPost->targets()->pluck('social_account_id')->all();

                foreach ($data['account_ids'] as $accountId) {
                    if (in_array($accountId, $existingIds, true)) {
                        continue;
                    }

                    $socialPost->targets()->create([
                        'social_account_id' => $accountId,
                        'status' => ($data['status'] ?? $socialPost->status) === 'draft'
                            ? 'pending'
                            : (($data['status'] ?? $socialPost->status) === 'scheduled' ? 'scheduled' : 'pending'),
                    ]);
                }
            }
        });

        if (array_key_exists('status', $data)) {
            $socialPost->targets()->update([
                'status' => $data['status'] === 'draft' ? 'pending' : $data['status'],
                'published_at' => $data['status'] === 'published' ? now() : null,
            ]);
        }

        if ($publishedTargets->isNotEmpty()) {
            try {
                foreach ($publishedTargets as $target) {
                    $connector = SocialConnectorRegistry::make($target->account);
                    $connector->updatePublishedPost($socialPost->fresh(), $target);
                }
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'post' => [$e->getMessage()],
                ]);
            }
        }

        return response()->json(
            $socialPost->fresh()->load(['creator:id,name', 'targets.account:id,name,platform,username'])
        );
    }

    public function destroyPost(SocialPost $socialPost)
    {
        $targets = $socialPost->targets()->with('account')->get();

        try {
            foreach ($targets->whereNotNull('external_post_id') as $target) {
                $connector = SocialConnectorRegistry::make($target->account);
                $connector->deletePublishedPost($target);
            }
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'post' => [$e->getMessage()],
            ]);
        }

        $socialPost->delete();

        return response()->json([
            'message' => 'Post deleted successfully.',
        ]);
    }

    public function publishPost(SocialPost $socialPost)
    {
        $socialPost->update([
            'status' => 'published',
            'published_at' => now(),
            'scheduled_for' => null,
            'failure_reason' => null,
        ]);

        // Update all targets optimistically; jobs will set external_post_id or revert to failed
        foreach ($socialPost->targets()->get() as $target) {
            $target->update([
                'status' => 'published',
                'published_at' => now(),
                'failure_reason' => null,
            ]);

            PublishSocialPostJob::dispatch($target->id);
        }

        return response()->json(
            $socialPost->fresh()->load(['creator:id,name', 'targets.account:id,name,platform,username'])
        );
    }

    public function syncPostInteractions(SocialPost $socialPost)
    {
        $targets = $socialPost->targets()->with('account')->get();

        if ($targets->isEmpty()) {
            throw ValidationException::withMessages([
                'post' => ['This post has no connected social targets to sync.'],
            ]);
        }

        $results = [];

        foreach ($targets as $target) {
            if (! $target->account) {
                continue;
            }

            $platform = $target->account->platform;

            if (! in_array($platform, ['facebook', 'instagram'], true)) {
                $results[] = [
                    'target_id' => $target->id,
                    'platform'  => $platform,
                    'status'    => 'skipped',
                    'message'   => ucfirst($platform) . ' interaction sync is not supported.',
                ];
                continue;
            }

            if (! filled($target->external_post_id)) {
                $results[] = [
                    'target_id' => $target->id,
                    'platform'  => $platform,
                    'status'    => 'skipped',
                    'message'   => 'Post has not been published yet — no external post ID to sync.',
                ];
                continue;
            }

            try {
                $connector = SocialConnectorRegistry::make($target->account);

                if ($platform === 'facebook') {
                    if (! $connector instanceof FacebookConnector) {
                        throw new \RuntimeException('Facebook connector unavailable.');
                    }
                    $sync = $connector->syncPostInteractions($target);
                } else {
                    if (! $connector instanceof InstagramConnector) {
                        throw new \RuntimeException('Instagram connector unavailable.');
                    }
                    $sync = $connector->syncPostComments($target);
                }

                $results[] = [
                    'target_id' => $target->id,
                    'platform'  => $platform,
                    'status'    => 'synced',
                    ...$sync,
                ];
            } catch (\Throwable $e) {
                if ($platform === 'facebook' && FacebookConnector::isExpiredTokenException($e)) {
                    $this->markAccountExpired($target->account, $e->getMessage());
                }

                $results[] = [
                    'target_id' => $target->id,
                    'platform'  => $platform,
                    'status'    => 'failed',
                    'message'   => $e->getMessage(),
                ];
            }
        }

        $failed = collect($results)->where('status', 'failed');

        if ($failed->isNotEmpty() && $failed->count() === count($results)) {
            throw ValidationException::withMessages([
                'post' => $failed->pluck('message')->filter()->values()->all(),
            ]);
        }

        $freshPost = SocialPost::query()
            ->with(['creator:id,name', 'targets.account:id,name,platform,username'])
            ->withCount('comments')
            ->findOrFail($socialPost->id);

        return response()->json([
            'post' => $this->serializePost($freshPost),
            'results' => $results,
        ]);
    }

    public function postComments(Request $request, SocialPost $socialPost)
    {
        $platform = $request->string('platform')->toString();

        $query = SocialComment::query()
            ->with(['account:id,name,platform,username', 'post:id,title,status', 'assignee:id,name'])
            ->where('social_post_id', $socialPost->id)
            ->latest('received_at');

        if (filled($platform) && $platform !== 'all') {
            $query->whereHas('account', fn ($accountQuery) => $accountQuery->where('platform', $platform));
        }

        return response()->json([
            'comments' => $query->get(),
        ]);
    }

    public function replyToComment(Request $request, SocialComment $socialComment)
    {
        $data = $request->validate([
            'reply_text' => 'required|string|max:2000',
        ]);

        $platformCommentId = trim((string) ($socialComment->platform_comment_id ?? ''));

        if (! filled($platformCommentId)) {
            return response()->json(['message' => 'This comment has no platform ID — it cannot be replied to on the platform.'], 422);
        }

        $socialComment->loadMissing('account');
        $account = $socialComment->account;

        if (! $account || $account->auth_status !== 'connected') {
            return response()->json(['message' => 'The social account for this comment is not connected. Reconnect and try again.'], 422);
        }

        try {
            $connector = SocialConnectorRegistry::make($account);
            $platformReplyId = $connector->publishCommentReply($platformCommentId, $data['reply_text']);
        } catch (\Throwable $e) {
            Log::error('Failed to publish comment reply to platform', [
                'comment_id'          => $socialComment->id,
                'platform_comment_id' => $platformCommentId,
                'platform'            => $account->platform,
                'error'               => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Failed to publish reply: '.$e->getMessage()], 422);
        }

        $socialComment->update([
            'reply_text'       => $data['reply_text'],
            'status'           => 'replied',
            'replied_at'       => now(),
            'assigned_user_id' => $request->user()?->id,
            'metadata'         => array_merge($socialComment->metadata ?? [], [
                'platform_reply_id' => $platformReplyId,
            ]),
        ]);

        return response()->json(
            $socialComment->fresh()->load(['account:id,name,platform,username', 'post:id,title,status', 'assignee:id,name'])
        );
    }

    public function updateComment(Request $request, SocialComment $socialComment)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['needs_reply', 'replied', 'ignored'])],
        ]);

        $socialComment->update([
            'status' => $data['status'],
            'replied_at' => $data['status'] === 'replied' ? ($socialComment->replied_at ?? now()) : null,
        ]);

        return response()->json(
            $socialComment->fresh()->load(['account:id,name,platform,username', 'post:id,title,status', 'assignee:id,name'])
        );
    }

    public function conversationMessages(SocialConversation $socialConversation)
    {
        $messages = $socialConversation->messages()
            ->orderByDesc('sent_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->sortBy(fn ($m) => $m->sent_at ?? $m->created_at)
            ->values();

        if ($socialConversation->unread_count > 0) {
            $socialConversation->update(['unread_count' => 0]);
        }

        return response()->json([
            'conversation_id' => $socialConversation->id,
            'messages' => $messages,
        ]);
    }

    public function sendMessage(Request $request, SocialConversation $socialConversation)
    {
        $data = $request->validate([
            'body' => 'required|string|max:3000',
        ]);

        $message = $socialConversation->messages()->create([
            'user_id' => $request->user()?->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'body' => $data['body'],
            'sent_at' => now(),
            'delivered_at' => now(),
            'read_at' => now(),
        ]);

        $socialConversation->update([
            'status' => 'open',
            'unread_count' => 0,
            'last_message_preview' => $data['body'],
            'last_message_at' => now(),
        ]);

        return response()->json([
            'conversation' => $socialConversation->fresh()->load([
                'account:id,name,platform,username,profile_image_url',
                'messages' => fn ($query) => $query->orderByDesc('sent_at')->orderByDesc('created_at')->limit(30),
            ]),
            'message' => $message,
        ]);
    }

    private function platformSnapshot(string $platform, Collection $accounts, Collection $posts, Collection $conversations): array
    {
        $platformAccounts = $accounts->where('platform', $platform);
        $platformAccountIds = $platformAccounts->pluck('id');

        return [
            'accounts' => $platformAccounts->count(),
            'connected_accounts' => $platformAccounts->where('auth_status', 'connected')->count(),
            'followers_count' => $platformAccounts->sum('followers_count'),
            'scheduled_posts' => $posts
                ->filter(fn ($post) => $post->targets->contains(fn ($target) => in_array($target->social_account_id, $platformAccountIds->all(), true)))
                ->where('status', 'scheduled')
                ->count(),
            'unread_conversations' => $conversations
                ->whereIn('social_account_id', $platformAccountIds)
                ->sum('unread_count'),
        ];
    }

    private function recentActivity(Collection $posts, Collection $conversations, Collection $comments): array
    {
        return collect()
            ->merge($posts->take(5)->map(function ($post) {
                return [
                    'type' => 'post',
                    'platform' => optional($post->targets->first()?->account)->platform,
                    'title' => $post->title ?: 'Untitled post',
                    'description' => $post->status === 'published' ? 'Post published successfully.' : 'Post saved in the publishing queue.',
                    'status' => $post->status,
                    'occurred_at' => optional($post->published_at ?? $post->scheduled_for ?? $post->created_at)?->toISOString(),
                ];
            }))
            ->merge($conversations->take(5)->map(function ($conversation) {
                return [
                    'type' => 'message',
                    'platform' => optional($conversation->account)->platform,
                    'title' => $conversation->customer_name,
                    'description' => $conversation->last_message_preview ?: 'New inbox activity.',
                    'status' => $conversation->status,
                    'occurred_at' => optional($conversation->last_message_at ?? $conversation->created_at)?->toISOString(),
                ];
            }))
            ->merge($comments->take(5)->map(function ($comment) {
                return [
                    'type' => 'comment',
                    'platform' => optional($comment->account)->platform,
                    'title' => $comment->author_name,
                    'description' => $comment->comment_text,
                    'status' => $comment->status,
                    'occurred_at' => optional($comment->received_at ?? $comment->created_at)?->toISOString(),
                ];
            }))
            ->sortByDesc('occurred_at')
            ->take(8)
            ->values()
            ->all();
    }

    private function defaultCapabilities(string $platform): array
    {
        return match ($platform) {
            'tiktok' => ['publish', 'schedule', 'messages'],
            'instagram' => ['publish', 'schedule', 'comments', 'messages'],
            default => ['publish', 'schedule', 'comments', 'messages'],
        };
    }

    private function serializeAccount(SocialAccount $account): array
    {
        $callbackUrls = [
            'oauth_callback_url' => url("/api/social-media/oauth/{$account->platform}/{$account->id}/callback"),
            'webhook_callback_url' => url("/api/social-media/webhooks/{$account->platform}/{$account->id}"),
        ];

        return [
            'id' => $account->id,
            'platform' => $account->platform,
            'name' => $account->name,
            'username' => $account->username,
            'external_account_id' => $account->external_account_id,
            'connection_type' => $account->connection_type,
            'auth_status' => $account->auth_status,
            'status' => $account->status,
            'capabilities' => $account->capabilities ?? [],
            'profile_image_url' => $account->profile_image_url,
            'followers_count' => $account->followers_count,
            'inbox_count' => $account->inbox_count,
            'auto_sync_enabled' => $account->auto_sync_enabled,
            'sync_frequency_minutes' => $account->sync_frequency_minutes,
            'sync_status' => $account->sync_status,
            'sync_error' => $account->sync_error,
            'token_expires_at' => optional($account->token_expires_at)?->toISOString(),
            'last_token_checked_at' => optional($account->last_token_checked_at)?->toISOString(),
            'last_sync_started_at' => optional($account->last_sync_started_at)?->toISOString(),
            'last_sync_completed_at' => optional($account->last_sync_completed_at)?->toISOString(),
            'last_webhook_at' => optional($account->last_webhook_at)?->toISOString(),
            'last_synced_at' => optional($account->last_synced_at)?->toISOString(),
            'is_active' => $account->is_active,
            'comments_count' => $account->comments_count,
            'conversations_count' => $account->conversations_count,
            'post_targets_count' => $account->post_targets_count,
            'connection_setup' => [
                'platform_label' => $this->platformLabel($account->platform),
                'credentials' => SocialConnectorRegistry::sanitizeCredentials($account->platform, $account->credentials ?? []),
                'required_credentials' => SocialConnectorRegistry::requiredCredentialKeys($account->platform),
                'callback_urls' => $callbackUrls,
                'webhook_verify_token' => $account->webhook_verify_token,
                'connection_ready' => $this->isConnectionReady($account),
                'reconnect_required' => in_array($account->auth_status, ['expired', 'error'], true),
            ],
        ];
    }

    private function markAccountExpired(SocialAccount $account, string $message, $expiresAt = null): void
    {
        $account->update([
            'auth_status' => 'expired',
            'status' => 'attention',
            'is_active' => false,
            'sync_status' => 'error',
            'sync_error' => mb_substr($message, 0, 500),
            'token_expires_at' => $expiresAt,
            'last_token_checked_at' => now(),
            'last_sync_completed_at' => now(),
        ]);
    }

    private function isConnectionReady(SocialAccount $account): bool
    {
        if (in_array($account->auth_status, ['expired', 'error', 'disconnected', 'pending'], true)) {
            return false;
        }

        // Token is known to be expired
        if ($account->token_expires_at && $account->token_expires_at->isPast()) {
            return false;
        }

        $creds = $account->credentials ?? [];

        $requiredSatisfied = collect(SocialConnectorRegistry::requiredCredentialKeys($account->platform))
            ->every(fn (string $key) => filled(data_get($creds, $key)));

        if (! $requiredSatisfied) {
            return false;
        }

        if ($account->platform === 'facebook') {
            return filled($creds['page_access_token'] ?? null)
                || filled($creds['user_access_token'] ?? null);
        }

        return true;
    }

    /**
     * Call the platform token introspection API and update the account status to reflect reality.
     * Skips silently if credentials are insufficient to validate.
     */
    private function validateAndUpdateAccountToken(SocialAccount $account): void
    {
        try {
            if ($account->platform === 'facebook') {
                $creds = $account->credentials ?? [];
                if (! filled($creds['app_id'] ?? '') || ! filled($creds['app_secret'] ?? '')) {
                    return;
                }
                if (! filled($creds['user_access_token'] ?? '') && ! filled($creds['page_access_token'] ?? '')) {
                    return;
                }

                $connector  = new FacebookConnector($creds);
                $inspection = $connector->inspectToken();

                $account->update([
                    'auth_status'           => ($inspection['is_valid'] ?? false) ? 'connected' : 'expired',
                    'status'                => ($inspection['is_valid'] ?? false) ? 'active' : 'attention',
                    'is_active'             => (bool) ($inspection['is_valid'] ?? false),
                    'token_expires_at'      => $inspection['expires_at'] ?? null,
                    'last_token_checked_at' => now(),
                    'sync_error'            => ($inspection['is_valid'] ?? false) ? null : ($inspection['message'] ?? null),
                ]);

            } elseif ($account->platform === 'instagram') {
                $creds = $account->credentials ?? [];
                if (! filled($creds['access_token'] ?? '') || ! filled($creds['app_id'] ?? '') || ! filled($creds['app_secret'] ?? '')) {
                    return;
                }

                $connector  = new InstagramConnector($creds);
                $inspection = $connector->validateToken();

                $account->update([
                    'auth_status'           => ($inspection['is_valid'] ?? false) ? 'connected' : 'expired',
                    'status'                => ($inspection['is_valid'] ?? false) ? 'active' : 'attention',
                    'is_active'             => (bool) ($inspection['is_valid'] ?? false),
                    'token_expires_at'      => $inspection['expires_at'] ?? null,
                    'last_token_checked_at' => now(),
                    'sync_error'            => ($inspection['is_valid'] ?? false) ? null : ($inspection['message'] ?? null),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Account token validation failed', [
                'account_id' => $account->id,
                'platform'   => $account->platform,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    public function syncAccountPosts(SocialAccount $socialAccount)
    {
        if (! in_array($socialAccount->platform, ['facebook', 'instagram', 'tiktok'], true)) {
            throw ValidationException::withMessages([
                'platform' => ['Post sync is only supported for Facebook, Instagram, and TikTok.'],
            ]);
        }

        try {
            $connector     = SocialConnectorRegistry::make($socialAccount);
            $platformPosts = $connector->syncPlatformPosts(25);

            $synced  = 0;
            $created = 0;

            foreach ($platformPosts as $item) {
                $externalId = $item['external_post_id'] ?? null;
                if (! filled($externalId)) {
                    continue;
                }

                // Find an existing target for this account + external post ID
                $existingTarget = $socialAccount->postTargets()
                    ->where('external_post_id', $externalId)
                    ->with('post')
                    ->first();

                if ($existingTarget) {
                    // Update the post content if it changed on the platform
                    $existingTarget->post?->update([
                        'content'   => $item['content'],
                        'media'     => $item['media'] ?? [],
                        'link_url'  => $item['platform_url'] ?? $existingTarget->post->link_url,
                    ]);
                    $synced++;
                } else {
                    // Create a new post record for a post that originated on the platform
                    $post = DB::transaction(function () use ($socialAccount, $item) {
                        $publishedAt = filled($item['published_at'] ?? null)
                            ? \Carbon\Carbon::parse($item['published_at'])
                            : now();

                        $post = SocialPost::create([
                            'title'        => null,
                            'content'      => $item['content'],
                            'media'        => $item['media'] ?? [],
                            'link_url'     => $item['platform_url'] ?? null,
                            'status'       => 'published',
                            'published_at' => $publishedAt,
                            'created_by'   => null,
                        ]);

                        $post->targets()->create([
                            'social_account_id' => $socialAccount->id,
                            'external_post_id'  => $item['external_post_id'],
                            'status'            => 'published',
                            'published_at'      => $publishedAt,
                        ]);

                        return $post;
                    });

                    $created++;
                }
            }

            Log::info('Platform posts synced', [
                'account_id' => $socialAccount->id,
                'platform'   => $socialAccount->platform,
                'synced'     => $synced,
                'created'    => $created,
            ]);

            return response()->json([
                'synced'  => $synced,
                'created' => $created,
                'total'   => $synced + $created,
            ]);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'sync' => [$e->getMessage()],
            ]);
        }
    }

    public function syncAccountMessages(SocialAccount $socialAccount)
    {
        if (! in_array($socialAccount->platform, ['facebook', 'instagram'], true)) {
            throw ValidationException::withMessages([
                'platform' => ['Message sync is only supported for Facebook and Instagram.'],
            ]);
        }

        if ($socialAccount->auth_status !== 'connected') {
            throw ValidationException::withMessages([
                'account' => ['Account must be connected to sync messages.'],
            ]);
        }

        try {
            $connector     = SocialConnectorRegistry::make($socialAccount);
            $conversations = $connector->syncConversations(20);

            $pageId       = $socialAccount->credentials['page_id'] ?? null;
            $igUserId     = $socialAccount->credentials['business_account_id'] ?? null;
            $ownerId      = $socialAccount->platform === 'facebook' ? $pageId : $igUserId;

            $syncedConversations = 0;
            $newMessages         = 0;

            DB::transaction(function () use ($socialAccount, $conversations, $ownerId, &$syncedConversations, &$newMessages) {
                foreach ($conversations as $conv) {
                    $threadId = (string) ($conv['id'] ?? '');
                    if (! filled($threadId)) {
                        continue;
                    }

                    $participants = $conv['participants']['data'] ?? [];
                    $customer     = collect($participants)->first(
                        fn ($p) => ($p['id'] ?? '') !== (string) $ownerId
                    );

                    $messages    = $conv['messages']['data'] ?? [];
                    $lastMessage = collect($messages)->sortByDesc('created_time')->first();

                    $conversation = SocialConversation::updateOrCreate(
                        ['social_account_id' => $socialAccount->id, 'platform_thread_id' => $threadId],
                        [
                            'customer_name'        => $customer['name'] ?? ($customer['username'] ?? 'Unknown'),
                            'customer_handle'      => $customer['username'] ?? ($customer['id'] ?? null),
                            'status'               => 'open',
                            'last_message_preview' => mb_substr($lastMessage['message'] ?? '', 0, 100),
                            'last_message_at'      => filled($lastMessage['created_time'] ?? null)
                                ? Carbon::parse($lastMessage['created_time'])
                                : now(),
                            'unread_count'         => (int) ($conv['unread_count'] ?? 0),
                            'metadata'             => ['participants' => $participants],
                        ]
                    );

                    $syncedConversations++;

                    foreach ($messages as $msg) {
                        $msgId = (string) ($msg['id'] ?? '');
                        if (! filled($msgId)) {
                            continue;
                        }

                        if (SocialMessage::where('external_message_id', $msgId)->exists()) {
                            continue;
                        }

                        $fromId    = (string) ($msg['from']['id'] ?? '');
                        $direction = ($fromId === (string) $ownerId) ? 'outbound' : 'inbound';

                        SocialMessage::create([
                            'social_conversation_id' => $conversation->id,
                            'external_message_id'    => $msgId,
                            'direction'              => $direction,
                            'message_type'           => 'text',
                            'body'                   => $msg['message'] ?? '',
                            'sent_at'                => filled($msg['created_time'] ?? null)
                                ? Carbon::parse($msg['created_time'])
                                : now(),
                            'metadata' => $msg,
                        ]);

                        $newMessages++;
                    }
                }
            });

            $socialAccount->update([
                'inbox_count'   => SocialConversation::where('social_account_id', $socialAccount->id)
                    ->where('status', 'open')
                    ->sum('unread_count'),
                'last_synced_at' => now(),
            ]);

            Log::info('Account messages synced', [
                'account_id'           => $socialAccount->id,
                'platform'             => $socialAccount->platform,
                'synced_conversations' => $syncedConversations,
                'new_messages'         => $newMessages,
            ]);

            return response()->json([
                'synced_conversations' => $syncedConversations,
                'new_messages'         => $newMessages,
                'message'              => "Synced {$syncedConversations} conversation(s), {$newMessages} new message(s).",
            ]);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'sync' => [$e->getMessage()],
            ]);
        }
    }

    private function serializePost(SocialPost $post): array
    {
        $targets = $post->targets->map(function ($target) {
            return [
                'id' => $target->id,
                'social_account_id' => $target->social_account_id,
                'status' => $target->status,
                'likes_count' => (int) ($target->likes_count ?? 0),
                'comments_count' => (int) ($target->comments_count ?? 0),
                'shares_count' => (int) ($target->shares_count ?? 0),
                'external_post_id' => $target->external_post_id,
                'published_at' => optional($target->published_at)?->toISOString(),
                'failure_reason' => $target->failure_reason,
                'account' => $target->account ? [
                    'id' => $target->account->id,
                    'name' => $target->account->name,
                    'platform' => $target->account->platform,
                    'username' => $target->account->username,
                ] : null,
            ];
        })->values();

        return [
            'id' => $post->id,
            'title' => $post->title,
            'content' => $post->content,
            'media' => $post->media ?? [],
            'link_url' => $post->link_url,
            'status' => $post->status,
            'scheduled_for' => optional($post->scheduled_for)?->toISOString(),
            'published_at' => optional($post->published_at)?->toISOString(),
            'failure_reason' => $post->failure_reason,
            'created_at' => optional($post->created_at)?->toISOString(),
            'updated_at' => optional($post->updated_at)?->toISOString(),
            'creator' => $post->creator ? [
                'id' => $post->creator->id,
                'name' => $post->creator->name,
            ] : null,
            'comments_count' => (int) ($post->comments_count ?? 0),
            'engagement' => [
                'likes' => (int) $targets->sum('likes_count'),
                'comments' => (int) $targets->sum('comments_count'),
                'shares' => (int) $targets->sum('shares_count'),
            ],
            'targets' => $targets->all(),
        ];
    }

    private function platformLabel(string $platform): string
    {
        return SocialConnectorRegistry::for($platform)['label'] ?? ucfirst($platform);
    }
}
