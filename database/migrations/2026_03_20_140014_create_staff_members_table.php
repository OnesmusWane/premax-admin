<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_members', function (Blueprint $table) {
            $table->id();

            // ── Identity ──────────────────────────────────────────────────
            $table->string('name');
            $table->string('role')
                  ->comment('Job title e.g. Founder & Lead Technician');
            $table->text('bio')->nullable()
                  ->comment('Short biography shown on About page');

            // ── Avatar ────────────────────────────────────────────────────
            $table->string('avatar_url')->nullable()
                  ->comment('Photo URL; falls back to initials if null');
            $table->string('initials', 3)->nullable()
                  ->comment('Auto-derived from name if left null e.g. JM');
            $table->string('avatar_color', 7)->default('#DC2626')
                  ->comment('Hex background color for initials avatar');

            // ── Contact (internal) ────────────────────────────────────────
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            // ── Visibility ────────────────────────────────────────────────
            $table->boolean('show_on_website')->default(true)
                  ->comment('Whether this staff member appears on the About page');
            $table->unsignedSmallInteger('sort_order')->default(0)
                  ->comment('Display order — lower = first');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_members');
    }
};