<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->enum('category', ['lubricants', 'cleaning', 'parts', 'tools', 'other'])
                  ->default('other');
            $table->unsignedInteger('stock_qty')->default(0);
            $table->unsignedInteger('reorder_level')->default(10)
                  ->comment('Alert triggers when stock_qty falls below this');
            $table->unsignedInteger('unit_price')->default(0)
                  ->comment('KES, selling price');
            $table->unsignedInteger('cost_price')->nullable()
                  ->comment('KES, purchase/cost price');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['stock_in', 'stock_out', 'adjustment']);
            $table->integer('quantity')->comment('Positive for in, negative for out');
            $table->integer('balance_after');
            $table->string('reference')->nullable()->comment('Invoice # or PO # if applicable');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
    }
};