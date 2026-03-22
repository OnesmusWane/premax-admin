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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();

            // ── Reviewer Info ─────────────────────────────────────────────
            $table->string('reviewer_name');
            $table->string('reviewer_email')->nullable()
                  ->comment('Optional — for internal reference only, not displayed');
            $table->string('reviewer_phone')->nullable()
                  ->comment('Optional — for internal reference only');
            $table->string('reviewer_avatar_url')->nullable()
                  ->comment('Profile photo URL; falls back to initials avatar if null');
            $table->string('reviewer_initials', 3)->nullable()
                  ->comment('Auto-generated fallback e.g. DO for David Ochieng');
            $table->string('reviewer_avatar_color', 7)->nullable()
                  ->comment('Hex background color for initials avatar e.g. #DC2626');

            // ── Review Content ────────────────────────────────────────────
            $table->unsignedTinyInteger('rating')
                  ->comment('Star rating 1–5');
            $table->text('body')
                  ->comment('The review text / testimonial');

            // ── Association ───────────────────────────────────────────────
            $table->foreignId('service_id')->nullable()
                  ->constrained('services')->nullOnDelete()
                  ->comment('Specific service reviewed, if known');
            $table->foreignId('service_category_id')->nullable()
                  ->constrained('service_categories')->nullOnDelete()
                  ->comment('Category of service reviewed, if known');

            // ── Source ────────────────────────────────────────────────────
            $table->enum('source', [
                'website',
                'google',
                'facebook',
                'instagram',
                'walk_in',
                'whatsapp',
                'other',
            ])->default('website')->comment('Where the review was submitted');
            $table->string('source_url')->nullable()
                  ->comment('Link to original review on Google, Facebook, etc.');
            $table->string('source_review_id')->nullable()
                  ->comment('External review ID from Google/Facebook for deduplication');

            // ── Verification ──────────────────────────────────────────────
            $table->boolean('is_verified_customer')->default(false)
                  ->comment('Whether the reviewer is a confirmed customer');
            $table->string('verification_reference')->nullable()
                  ->comment('Booking or invoice reference used to verify customer');

            // ── Moderation ────────────────────────────────────────────────
            $table->enum('status', ['pending', 'approved', 'rejected', 'flagged'])
                  ->default('pending')
                  ->comment('Moderation status before displaying publicly');
            $table->text('moderation_note')->nullable()
                  ->comment('Internal note from the moderator');
            $table->boolean('is_featured')->default(false)
                  ->comment('Pin to homepage / hero testimonials section');
            $table->boolean('show_on_website')->default(false)
                  ->comment('Final toggle for public visibility');

            // ── Response ──────────────────────────────────────────────────
            $table->text('owner_response')->nullable()
                  ->comment('Public business response to the review');
            $table->timestamp('owner_responded_at')->nullable();

            // ── Timestamps ────────────────────────────────────────────────
            $table->timestamp('reviewed_at')->nullable()
                  ->comment('Date the customer wrote the review (may differ from created_at for imported reviews)');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};