<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->text('long_description')->nullable()->after('description');
            $table->json('features')->nullable()->after('long_description');   // ["Feature one", "Feature two"]
            $table->json('process')->nullable()->after('features');             // [{"step":"01","title":"...","detail":"..."}]
            $table->string('image')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['long_description', 'features', 'process', 'image']);
        });
    }
};
