<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingSourcesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
       $now = Carbon::now();
        $sources = [
            [
                'name' => 'Website',
                'slug' => 'website',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Whatsapp',
                'slug' => 'whatsapp',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Phone call',
                'slug' => 'phone-call',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Referral',
                'slug' => 'referral',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'name' => 'Walk in',
                'slug' => 'walk-in',
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        foreach ($sources as $source) {
            DB::table('booking_sources')->updateOrInsert(
                ['slug' => $source['slug']],
                $source
            );
        }
    }
}
