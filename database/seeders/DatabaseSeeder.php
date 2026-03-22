<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\BookingStatusesSeeder;
use Database\Seeders\DepartmentsSeeder;
use Database\Seeders\ServicesSeeder;
use Database\Seeders\ContactInformationSeeder;
use Database\Seeders\LegalPagesSeeder;
use Database\Seeders\ReviewsSeeder;
use Database\Seeders\StaffMembersSeeder;
use Database\Seeders\BookingSourcesSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BookingStatusesSeeder::class,
            DepartmentsSeeder::class,
            BookingSourcesSeeder::class,
            ServicesSeeder::class,
            ContactInformationSeeder::class,
            LegalPagesSeeder::class,
            ReviewsSeeder::class,
            StaffMembersSeeder::class,
        ]);
    }
}
