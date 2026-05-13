<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comment_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->text('body');
            $table->enum('platform', ['facebook', 'instagram', 'tiktok', 'all'])->nullable();
            $table->string('shortcut', 30)->nullable()->unique();
            $table->integer('usage_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('platform');
            $table->index('shortcut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_templates');
    }
};
