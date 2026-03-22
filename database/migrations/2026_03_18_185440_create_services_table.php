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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_category_id')->constrained('service_categories')->cascadeOnDelete(); 
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable()->comment('Heroicon or Lucide icon name, e.g. wrench, cog, battery-charging'); 
            // Pricing
            $table->unsignedInteger('price_from')->comment('Minimum estimated price in KES');
            $table->unsignedInteger('price_to')->nullable()->comment('Maximum estimated price in KES, null if fixed');
            $table->boolean('price_is_estimate')->default(true)->comment('Whether the price is an estimate or fixed'); 
            // Duration
            $table->unsignedSmallInteger('duration_minutes')->nullable()->comment('Estimated time to complete service in minutes');
             
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
 
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
