<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('long_description')->nullable()->after('description');
            $table->json('features')->nullable()->after('long_description');
            $table->json('gallery')->nullable()->after('features');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['long_description', 'features', 'gallery']);
        });
    }
};
