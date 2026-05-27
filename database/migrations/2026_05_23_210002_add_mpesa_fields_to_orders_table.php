<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending')->after('payment_reference');
            $table->string('mpesa_checkout_request_id', 100)->nullable()->after('payment_status');
            $table->string('mpesa_transaction_id', 100)->nullable()->after('mpesa_checkout_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'mpesa_checkout_request_id', 'mpesa_transaction_id']);
        });
    }
};
