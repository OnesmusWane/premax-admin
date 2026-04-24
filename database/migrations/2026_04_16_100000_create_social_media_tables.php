<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('platform', 30);
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('external_account_id')->nullable();
            $table->string('connection_type', 30)->default('oauth');
            $table->string('auth_status', 30)->default('pending');
            $table->string('status', 30)->default('active');
            $table->json('capabilities')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->unsignedBigInteger('followers_count')->default(0);
            $table->unsignedInteger('inbox_count')->default(0);
            $table->longText('credentials')->nullable();
            $table->boolean('auto_sync_enabled')->default(true);
            $table->unsignedSmallInteger('sync_frequency_minutes')->default(15);
            $table->string('sync_status', 30)->default('idle');
            $table->text('sync_error')->nullable();
            $table->timestamp('last_sync_started_at')->nullable();
            $table->timestamp('last_sync_completed_at')->nullable();
            $table->timestamp('last_webhook_at')->nullable();
            $table->string('webhook_verify_token', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['platform', 'auth_status']);
            $table->index(['status', 'is_active']);
            $table->index(['auto_sync_enabled', 'sync_status']);
        });

        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('content');
            $table->json('media')->nullable();
            $table->string('link_url')->nullable();
            $table->string('status', 30)->default('draft');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_for']);
            $table->index(['published_at']);
        });

        Schema::create('social_post_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_post_id')->constrained('social_posts')->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('status', 30)->default('pending');
            $table->string('external_post_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['social_post_id', 'social_account_id']);
            $table->index(['status', 'published_at']);
        });

        Schema::create('social_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->string('platform_thread_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_handle')->nullable();
            $table->string('customer_avatar_url')->nullable();
            $table->string('status', 30)->default('open');
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('priority', 20)->default('normal');
            $table->text('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
            $table->index(['social_account_id', 'last_message_at']);
        });

        Schema::create('social_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_conversation_id')->constrained('social_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_message_id')->nullable();
            $table->string('direction', 20);
            $table->string('message_type', 20)->default('text');
            $table->text('body');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['social_conversation_id', 'sent_at']);
            $table->index(['direction', 'read_at']);
        });

        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->foreignId('social_post_id')->nullable()->constrained('social_posts')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('platform_comment_id')->nullable();
            $table->string('author_name');
            $table->string('author_handle')->nullable();
            $table->text('comment_text');
            $table->string('status', 30)->default('needs_reply');
            $table->text('reply_text')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['status', 'received_at']);
            $table->index(['social_account_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_comments');
        Schema::dropIfExists('social_messages');
        Schema::dropIfExists('social_conversations');
        Schema::dropIfExists('social_post_targets');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_accounts');
    }
};
