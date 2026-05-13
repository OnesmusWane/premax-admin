<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_library', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('url', 2048);
            $table->string('cloudinary_public_id', 500);
            $table->string('mime_type', 100);
            $table->enum('type', ['image', 'video']);
            $table->unsignedBigInteger('size');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable();
            $table->json('tags')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('type');
            $table->index('created_by');
            $table->index('last_used_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_library');
    }
};
