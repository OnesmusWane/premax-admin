<?php

namespace App\Support;

use App\Models\SocialAccount;
use App\Services\Social\Contracts\SocialPlatformPublisher;
use App\Services\Social\FacebookConnector;
use App\Services\Social\InstagramConnector;
use App\Services\Social\TikTokConnector;
use InvalidArgumentException;

class SocialConnectorRegistry
{
    public static function all(): array
    {
        return [
            'tiktok' => [
                'label' => 'TikTok',
                'connection_type' => 'oauth',
                'credentials' => [
                    // App credentials (from TikTok Developer Portal → your app → Key & Secret)
                    ['key' => 'client_key',    'label' => 'Client Key',     'secret' => false, 'required' => true,  'hint' => 'Found in TikTok Developer Portal → your app → Key & Secret'],
                    ['key' => 'client_secret', 'label' => 'Client Secret',  'secret' => true,  'required' => true,  'hint' => 'Found in TikTok Developer Portal → your app → Key & Secret'],
                    // Per-user credentials (obtained after OAuth)
                    ['key' => 'open_id',       'label' => 'Open ID',        'secret' => false, 'required' => true,  'hint' => 'Returned alongside the access token during OAuth — the user\'s unique TikTok identifier'],
                    ['key' => 'access_token',  'label' => 'Access Token',   'secret' => true,  'required' => true,  'hint' => 'OAuth access token with video.publish scope'],
                    ['key' => 'refresh_token', 'label' => 'Refresh Token',  'secret' => true,  'required' => false, 'hint' => 'Used to refresh the access token when it expires'],
                    ['key' => 'advertiser_id', 'label' => 'Advertiser ID',  'secret' => false, 'required' => false, 'hint' => 'Optional — only needed for TikTok Ads API integration'],
                ],
            ],
            'facebook' => [
                'label' => 'Facebook',
                'connection_type' => 'oauth',
                'credentials' => [
                    // App credentials — Meta Developer Portal → your app → Settings → Basic
                    ['key' => 'app_id',             'label' => 'App ID',              'secret' => false, 'required' => true,  'hint' => 'Found in Meta Developer Portal → your app → Settings → Basic'],
                    ['key' => 'app_secret',         'label' => 'App Secret',          'secret' => true,  'required' => true,  'hint' => 'Found in Meta Developer Portal → your app → Settings → Basic'],
                    // Page targeting
                    ['key' => 'page_id',            'label' => 'Page ID',             'secret' => false, 'required' => true,  'hint' => 'Numeric ID of the Facebook Page to post on — Page → About → Page Transparency'],
                    // v25.0 token: provide either a ready-made Page token OR a User token for auto-exchange
                    ['key' => 'user_access_token',  'label' => 'User Access Token',   'secret' => true,  'required' => false, 'hint' => 'v25.0 preferred: a long-lived User token with pages_read_engagement + pages_manage_metadata scopes — the connector exchanges it for a Page token automatically'],
                    ['key' => 'page_access_token',  'label' => 'Page Access Token',   'secret' => true,  'required' => false, 'hint' => 'Alternative: paste a Page Access Token directly (obtained via Graph API Explorer or your OAuth callback). Leave blank if using User Access Token above'],
                    ['key' => 'business_id',        'label' => 'Business ID',         'secret' => false, 'required' => false, 'hint' => 'Optional — Meta Business Manager ID'],
                ],
            ],
            'instagram' => [
                'label' => 'Instagram',
                'connection_type' => 'oauth',
                'credentials' => [
                    // App credentials (same FB app used for Instagram Graph API)
                    ['key' => 'app_id',              'label' => 'App ID (Facebook)',           'secret' => false, 'required' => true,  'hint' => 'Same Facebook App ID — Instagram uses the Facebook Graph API'],
                    ['key' => 'app_secret',          'label' => 'App Secret (Facebook)',       'secret' => true,  'required' => true,  'hint' => 'Same Facebook App Secret'],
                    // Per-account credentials (required to post)
                    ['key' => 'business_account_id', 'label' => 'Instagram Business Account ID', 'secret' => false, 'required' => true,  'hint' => 'Numeric IG User ID — find via Graph API: GET /me/accounts then check instagram_business_account.id'],
                    ['key' => 'access_token',        'label' => 'Access Token',                'secret' => true,  'required' => true,  'hint' => 'Long-lived token with instagram_basic and instagram_content_publish permissions'],
                    ['key' => 'page_id',             'label' => 'Linked Facebook Page ID',     'secret' => false, 'required' => false, 'hint' => 'Optional — the Facebook Page connected to this Instagram Business Account'],
                ],
            ],
        ];
    }

    public static function for(string $platform): array
    {
        return static::all()[$platform] ?? [
            'label' => ucfirst($platform),
            'connection_type' => 'oauth',
            'credentials' => [],
        ];
    }

    public static function credentialRules(string $platform): array
    {
        $rules = ['nullable', 'array'];

        foreach (static::for($platform)['credentials'] as $field) {
            $rulesByField["credentials.{$field['key']}"] = $field['required']
                ? ['required', 'string', 'max:5000']
                : ['nullable', 'string', 'max:5000'];
        }

        return [$rules, $rulesByField ?? []];
    }

    public static function sanitizeCredentials(string $platform, array $credentials): array
    {
        $definition = collect(static::for($platform)['credentials'])->keyBy('key');

        return collect($credentials)->mapWithKeys(function ($value, $key) use ($definition) {
            $field = $definition->get($key);

            if (! $field) {
                return [$key => $value];
            }

            if (! ($field['secret'] ?? false)) {
                return [$key => $value];
            }

            if (! filled($value)) {
                return [$key => null];
            }

            $stringValue = (string) $value;

            if (strlen($stringValue) <= 6) {
                return [$key => str_repeat('*', strlen($stringValue))];
            }

            return [$key => str_repeat('*', max(strlen($stringValue) - 4, 4)).substr($stringValue, -4)];
        })->all();
    }

    public static function mergeCredentials(string $platform, array $existing, array $incoming): array
    {
        $definition = collect(static::for($platform)['credentials'])->keyBy('key');
        $merged = $existing;

        foreach ($incoming as $key => $value) {
            $field = $definition->get($key);

            if (! $field) {
                $merged[$key] = $value;
                continue;
            }

            $isSecret = (bool) ($field['secret'] ?? false);

            if ($isSecret && ! filled($value)) {
                continue;
            }

            $merged[$key] = $value;
        }

        return array_filter($merged, fn ($value) => $value !== null && $value !== '');
    }

    public static function requiredCredentialKeys(string $platform): array
    {
        return collect(static::for($platform)['credentials'])
            ->filter(fn (array $field) => $field['required'] ?? false)
            ->pluck('key')
            ->all();
    }

    public static function make(SocialAccount $account): SocialPlatformPublisher
    {
        // Proactively refresh Facebook tokens that are expired or expire within 48 hours,
        // so the connector always starts with a valid token rather than failing mid-request.
        if ($account->platform === 'facebook') {
            $expiresAt = $account->token_expires_at;
            $needsRefresh = $expiresAt === null
                || $expiresAt->isPast()
                || $expiresAt->diffInHours(now(), true) <= 48;

            $creds = $account->credentials ?? [];
            $canRefresh = filled($creds['app_id'] ?? '')
                && filled($creds['app_secret'] ?? '')
                && filled($creds['user_access_token'] ?? '');

            if ($needsRefresh && $canRefresh) {
                FacebookConnector::tryRefreshAccount($account);
                $account->refresh();
            }
        }

        $credentials = $account->credentials ?? [];

        return match ($account->platform) {
            'facebook' => new FacebookConnector($credentials),
            'instagram' => new InstagramConnector($credentials),
            'tiktok' => new TikTokConnector($credentials),
            default => throw new InvalidArgumentException("No connector registered for platform: {$account->platform}"),
        };
    }
}
