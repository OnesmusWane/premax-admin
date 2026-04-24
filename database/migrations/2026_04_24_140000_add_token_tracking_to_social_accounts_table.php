<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->timestamp('token_expires_at')->nullable()->after('last_webhook_at');
            $table->timestamp('last_token_checked_at')->nullable()->after('token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropColumn(['token_expires_at', 'last_token_checked_at']);
        });
    }
};
