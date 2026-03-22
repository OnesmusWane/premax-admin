<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentsSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
 
        DB::table('departments')->insert([
            ['name' => 'Workshop',          'slug' => 'workshop',          'description' => 'Vehicle servicing, repairs and diagnostics.',         'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Tyre Centre',        'slug' => 'tyre-centre',       'description' => 'Tyre fitting, alignment and balancing.',              'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Customer Service',   'slug' => 'customer-service',  'description' => 'Bookings, customer relations and front desk.',         'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Finance',            'slug' => 'finance',           'description' => 'Payments, invoicing and financial management.',        'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Management',         'slug' => 'management',        'description' => 'Senior management and administration.',               'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}
