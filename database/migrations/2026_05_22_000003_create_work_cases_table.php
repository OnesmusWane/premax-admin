<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('category', ['detailing', 'performance', 'bodywork', 'diagnostics'])->default('detailing');
            $table->string('service_type')->nullable();       // e.g. "Full Paint Correction"
            $table->string('before_image')->nullable();
            $table->string('after_image')->nullable();
            $table->text('brief')->nullable();               // The Brief section
            $table->text('challenge')->nullable();           // The Challenge section
            $table->text('outcome')->nullable();             // The Outcome section
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->date('completed_at')->nullable();
            $table->string('client_type')->nullable();       // e.g. "Private Owner"
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_cases');
    }
};
