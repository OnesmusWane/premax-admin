<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['stock_in', 'stock_out', 'adjustment', 'order'])->default('adjustment');
            $table->string('source_ref', 60)->nullable()->comment('Order number if type=order');
            $table->integer('quantity')->comment('Positive for in, negative for out');
            $table->integer('balance_after');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_movements');
    }
};
