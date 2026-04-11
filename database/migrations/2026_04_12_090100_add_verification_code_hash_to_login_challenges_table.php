<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_challenges', function (Blueprint $table) {
            $table->string('verification_code_hash')->nullable()->after('token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('login_challenges', function (Blueprint $table) {
            $table->dropColumn('verification_code_hash');
        });
    }
};
