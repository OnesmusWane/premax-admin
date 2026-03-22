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
        Schema::create('contact_information', function (Blueprint $table) {
            $table->id();

            // ── General ───────────────────────────────────────────────────
            $table->string('label')->default('main')
                  ->comment('Identifier e.g. main, branch, head-office');

            // ── Phone Numbers ─────────────────────────────────────────────
            $table->string('phone_primary')->nullable()
                  ->comment('Primary phone number e.g. +254 742 091 794');
            $table->string('phone_secondary')->nullable()
                  ->comment('Secondary / alternate phone number');
            $table->string('phone_whatsapp')->nullable()
                  ->comment('WhatsApp contact number if different from primary');

            // ── Email ─────────────────────────────────────────────────────
            $table->string('email_primary')->nullable()
                  ->comment('Main business email address');
            $table->string('email_support')->nullable()
                  ->comment('Customer support / bookings email');

            // ── Social Media ──────────────────────────────────────────────
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('linkedin_url')->nullable();

            // ── Website ───────────────────────────────────────────────────
            $table->string('website_url')->nullable();

            // ── Postal Address ────────────────────────────────────────────
            $table->string('po_box')->nullable()
                  ->comment('P.O. Box number e.g. 58230');
            $table->string('postal_code')->nullable()
                  ->comment('Postal / ZIP code e.g. 00200');
            $table->string('postal_town')->nullable()
                  ->comment('Town for postal address e.g. Nairobi');
            $table->string('country')->default('Kenya');

            // ── Physical / Street Address ─────────────────────────────────
            $table->string('street_address')->nullable()
                  ->comment('Street or road name e.g. Kiambu Road / Northern Bypass Junction');
            $table->string('landmark')->nullable()
                  ->comment('Nearby landmark e.g. Next to Glee Hotel');
            $table->string('building')->nullable()
                  ->comment('Building or premises name if applicable');
            $table->string('floor_unit')->nullable()
                  ->comment('Floor or unit number if applicable');
            $table->string('area')->nullable()
                  ->comment('Estate, suburb or area e.g. Ruaka');
            $table->string('city')->default('Nairobi');
            $table->string('county')->nullable()
                  ->comment('County e.g. Nairobi, Kiambu');

            // ── Map / GPS Coordinates ─────────────────────────────────────
            $table->decimal('latitude', 10, 7)->nullable()
                  ->comment('GPS latitude in decimal degrees e.g. -1.2181690');
            $table->decimal('longitude', 10, 7)->nullable()
                  ->comment('GPS longitude in decimal degrees e.g. 36.7972270');
            $table->unsignedTinyInteger('map_zoom')->default(16)
                  ->comment('Default Google Maps zoom level 1-20');
            $table->string('google_maps_url')->nullable()
                  ->comment('Full Google Maps share / embed link');
            $table->string('google_place_id')->nullable()
                  ->comment('Google Places ID for rich map integration');
            $table->string('what3words')->nullable()
                  ->comment('what3words address e.g. ///filled.count.soap');

            // ── Business Hours ────────────────────────────────────────────
            $table->json('business_hours')->nullable()
                  ->comment('JSON object keyed by day with open/close times');

            // ── Status ────────────────────────────────────────────────────
            $table->boolean('is_primary')->default(true)
                  ->comment('Whether this is the main/primary contact record');
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_information');
    }
};