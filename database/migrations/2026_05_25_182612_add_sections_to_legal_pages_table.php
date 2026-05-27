<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->json('sections')->nullable()->after('content')
                  ->comment('Structured array of {title, body} objects');
            $table->string('description')->nullable()->after('title')
                  ->comment('Short subtitle shown under the hero heading');
        });
    }

    public function down(): void
    {
        Schema::table('legal_pages', function (Blueprint $table) {
            $table->dropColumn(['sections', 'description']);
        });
    }
};
