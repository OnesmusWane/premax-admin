<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\SocialAccount;
use App\Models\SocialConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SocialMediaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_create_and_publish_a_social_post(): void
    {
        $user = User::factory()->create();
        $permissions = collect([
            ['name' => 'View Social Media', 'slug' => 'social_media.view'],
            ['name' => 'Manage Social Accounts', 'slug' => 'social_media.accounts.manage'],
            ['name' => 'Manage Social Inbox', 'slug' => 'social_media.inbox.manage'],
            ['name' => 'Manage Social Publishing', 'slug' => 'social_media.posts.manage'],
            ['name' => 'Manage Social Engagement', 'slug' => 'social_media.engagement.manage'],
        ])->map(function (array $permission) {
            return Permission::query()->create([
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'group_name' => 'Marketing',
            ]);
        });

        $user->directPermissions()->attach($permissions->pluck('id'));

        Sanctum::actingAs($user);

        $account = SocialAccount::query()->create([
            'platform' => 'facebook',
            'name' => 'Premax Autocare',
            'username' => '@premaxautocare',
            'auth_status' => 'connected',
            'status' => 'active',
            'capabilities' => ['publish', 'schedule', 'comments', 'messages'],
            'is_active' => true,
            'connected_by' => $user->id,
        ]);

        $createResponse = $this->postJson('/api/admin/social-media/posts', [
            'title' => 'Weekend Offer',
            'content' => 'Book a detailing slot this weekend.',
            'account_ids' => [$account->id],
            'publish_now' => false,
            'scheduled_for' => now()->addDay()->toISOString(),
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('status', 'scheduled')
            ->assertJsonPath('targets.0.account.id', $account->id);

        $publishResponse = $this->postJson('/api/admin/social-media/posts/'.$createResponse->json('id').'/publish');

        $publishResponse
            ->assertOk()
            ->assertJsonPath('status', 'published')
            ->assertJsonPath('targets.0.status', 'published');
    }

    public function test_authorized_user_can_view_social_workspace_and_reply_to_inbox(): void
    {
        $user = User::factory()->create();
        $permissions = collect([
            ['name' => 'View Social Media', 'slug' => 'social_media.view'],
            ['name' => 'Manage Social Accounts', 'slug' => 'social_media.accounts.manage'],
            ['name' => 'Manage Social Inbox', 'slug' => 'social_media.inbox.manage'],
        ])->map(function (array $permission) {
            return Permission::query()->create([
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'group_name' => 'Marketing',
            ]);
        });

        $user->directPermissions()->attach($permissions->pluck('id'));

        Sanctum::actingAs($user);

        $account = SocialAccount::query()->create([
            'platform' => 'instagram',
            'name' => 'Premax Autocare',
            'username' => '@premaxautocare',
            'auth_status' => 'connected',
            'status' => 'active',
            'capabilities' => ['publish', 'schedule', 'messages'],
            'followers_count' => 8200,
            'is_active' => true,
            'connected_by' => $user->id,
        ]);

        $conversation = SocialConversation::query()->create([
            'social_account_id' => $account->id,
            'customer_name' => 'Sarah Jenkins',
            'customer_handle' => '@sarahj',
            'status' => 'open',
            'unread_count' => 2,
            'last_message_preview' => 'Hello there!',
            'last_message_at' => now(),
        ]);

        $conversation->messages()->create([
            'direction' => 'inbound',
            'message_type' => 'text',
            'body' => 'Hello there!',
            'sent_at' => now()->subMinute(),
        ]);

        $indexResponse = $this->getJson('/api/admin/social-media');

        $indexResponse
            ->assertOk()
            ->assertJsonPath('summary.connected_accounts', 1)
            ->assertJsonPath('platforms.instagram.connected_accounts', 1)
            ->assertJsonPath('conversations.0.customer_name', 'Sarah Jenkins');

        $sendResponse = $this->postJson("/api/admin/social-media/conversations/{$conversation->id}/messages", [
            'body' => 'Thanks for reaching out. We have this in stock.',
        ]);

        $sendResponse
            ->assertOk()
            ->assertJsonPath('conversation.id', $conversation->id)
            ->assertJsonPath('conversation.messages.0.direction', 'outbound');
    }

    public function test_authorized_user_can_connect_account_with_credentials_and_receive_webhook(): void
    {
        $user = User::factory()->create();
        $permissions = collect([
            ['name' => 'View Social Media', 'slug' => 'social_media.view'],
            ['name' => 'Manage Social Accounts', 'slug' => 'social_media.accounts.manage'],
        ])->map(function (array $permission) {
            return Permission::query()->create([
                'name' => $permission['name'],
                'slug' => $permission['slug'],
                'group_name' => 'Marketing',
            ]);
        });

        $user->directPermissions()->attach($permissions->pluck('id'));

        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/admin/social-media/accounts', [
            'platform' => 'facebook',
            'name' => 'Premax Facebook',
            'username' => '@premaxfb',
            'connection_type' => 'oauth',
            'credentials' => [
                'app_id' => 'fb-app-id',
                'app_secret' => 'fb-app-secret',
            ],
            'auto_sync_enabled' => true,
            'sync_frequency_minutes' => 15,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('platform', 'facebook')
            ->assertJsonPath('connection_setup.connection_ready', true)
            ->assertJsonPath('auto_sync_enabled', true);

        $accountId = $createResponse->json('id');
        $verifyToken = $createResponse->json('connection_setup.webhook_verify_token');

        $syncResponse = $this->postJson("/api/admin/social-media/accounts/{$accountId}/sync");

        $syncResponse
            ->assertOk()
            ->assertJsonPath('sync_status', 'synced');

        $webhookResponse = $this->postJson(
            "/api/social-media/webhooks/facebook/{$accountId}?verify_token={$verifyToken}",
            ['unread_count' => 5]
        );

        $webhookResponse
            ->assertOk()
            ->assertJsonPath('account_id', $accountId);
    }
}
