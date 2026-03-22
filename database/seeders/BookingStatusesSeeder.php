<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $now = Carbon::now();
        $statuses = [
            [
                'name' => 'Pending',
                'slug' => 'pending',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Confirmed',
                'slug' => 'confirmed',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Inprogress',
                'slug' => 'inprogress',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Cancelled',
                'slug' => 'canceled',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Complete',
                'slug' => 'complete',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'No show',
                'slug' => 'no-show',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('booking_statuses')->updateOrInsert(
                ['slug' => $status['slug']],
                $status
            );
        }
    }
}
