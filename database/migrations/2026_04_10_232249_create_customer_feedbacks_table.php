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
        Schema::create('customer_feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_token_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('vehicle')->nullable();
            $table->string('service')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('liked')->nullable();
            $table->text('suggestions')->nullable();
            $table->enum('recommend', ['yes', 'no'])->default('yes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_feedbacks');
    }
};
