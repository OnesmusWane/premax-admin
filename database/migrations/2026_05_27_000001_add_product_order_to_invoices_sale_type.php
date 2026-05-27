<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY sale_type ENUM('booking','direct_sale','booking_deposit','product_order') NOT NULL DEFAULT 'direct_sale'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE invoices MODIFY sale_type ENUM('booking','direct_sale','booking_deposit') NOT NULL DEFAULT 'direct_sale'");
    }
};
