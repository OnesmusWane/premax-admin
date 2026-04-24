<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_post_targets', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->after('status');
            $table->unsignedInteger('comments_count')->default(0)->after('likes_count');
            $table->unsignedInteger('shares_count')->default(0)->after('comments_count');
        });
    }

    public function down(): void
    {
        Schema::table('social_post_targets', function (Blueprint $table) {
            $table->dropColumn(['likes_count', 'comments_count', 'shares_count']);
        });
    }
};
