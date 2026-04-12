<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('is_anonymous')->default(false)->after('email');
        });

        DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NULL');
        DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NULL');

        Schema::table('services', function (Blueprint $table) {
            $table->boolean('requires_deposit')->default(false)->after('duration_minutes');
            $table->unsignedTinyInteger('deposit_percent')->nullable()->after('requires_deposit');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('anonymous_customer_name')->nullable()->after('customer_id');
            $table->boolean('requires_deposit')->default(false)->after('staff_notes');
            $table->unsignedTinyInteger('deposit_percent')->nullable()->after('requires_deposit');
            $table->unsignedInteger('deposit_required_amount')->default(0)->after('deposit_percent');
            $table->unsignedInteger('deposit_paid_amount')->default(0)->after('deposit_required_amount');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('sale_type', ['booking', 'direct_sale', 'booking_deposit'])->default('direct_sale')->after('booking_id');
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('gateway_reference')->nullable()->after('mpesa_reference');
            $table->string('anonymous_customer_name')->nullable()->after('customer_id');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->enum('line_type', ['service', 'inventory', 'custom', 'deposit'])->default('custom')->after('invoice_id');
            $table->foreignId('service_id')->nullable()->after('line_type')->constrained('services')->nullOnDelete();
            $table->foreignId('inventory_item_id')->nullable()->after('service_id')->constrained('inventory_items')->nullOnDelete();
            $table->json('meta')->nullable()->after('total');
        });

        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->string('provider')->default('kopokopo')->after('id');
            $table->string('location')->nullable()->after('checkout_request_id');
            $table->string('internal_reference')->nullable()->after('location');
            $table->json('callback_payload')->nullable()->after('result_desc');
        });
    }

    public function down(): void
    {
        Schema::table('mpesa_transactions', function (Blueprint $table) {
            $table->dropColumn(['provider', 'location', 'internal_reference', 'callback_payload']);
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('inventory_item_id');
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn(['line_type', 'meta']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['sale_type', 'payment_provider', 'gateway_reference', 'anonymous_customer_name']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'anonymous_customer_name',
                'requires_deposit',
                'deposit_percent',
                'deposit_required_amount',
                'deposit_paid_amount',
            ]);
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['requires_deposit', 'deposit_percent']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('is_anonymous');
        });

        DB::statement('ALTER TABLE customers MODIFY phone VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE customers MODIFY email VARCHAR(255) NULL');
    }
};
