<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number', 30)->unique();
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            $table->string('contact_email', 191);
            $table->string('delivery_first_name', 100);
            $table->string('delivery_last_name', 100);
            $table->string('delivery_address', 255);
            $table->string('delivery_city', 100)->default('Nairobi');
            $table->string('delivery_phone', 30);
            $table->enum('payment_method', ['card', 'mpesa'])->default('mpesa');
            $table->string('payment_reference', 100)->nullable();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
