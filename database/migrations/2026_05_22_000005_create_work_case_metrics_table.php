<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_case_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_case_id')->constrained()->cascadeOnDelete();
            $table->string('label');     // e.g. "Paint Thickness Restored"
            $table->string('value');     // e.g. "98%"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_case_metrics');
    }
};
