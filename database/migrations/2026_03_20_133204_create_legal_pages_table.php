<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_pages', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique()
                  ->comment('e.g. privacy_policy, terms_of_service');
            $table->string('title');
            $table->string('slug')->unique()
                  ->comment('URL slug e.g. privacy-policy');
            $table->longText('content')
                  ->comment('Full HTML or Markdown content of the page');
            $table->string('version')->nullable()
                  ->comment('e.g. 1.0, 2.1 — bump when content changes');
            $table->timestamp('effective_date')->nullable()
                  ->comment('Date this version takes effect');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_pages');
    }
};