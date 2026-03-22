<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ContactInformationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         DB::table('contact_information')->insert([
            'label' => 'main',
 
            // ── Phone ─────────────────────────────────────────────────────
            'phone_primary'   => '+254742091794',
            'phone_secondary' => '+254722219396',
            'phone_whatsapp'  => '+254742091794',   // assumed same as primary
 
            // ── Email ─────────────────────────────────────────────────────
            'email_primary'   => null,   // update when available
            'email_support'   => null,   // update when available
 
            // ── Social Media ──────────────────────────────────────────────
            'facebook_url'  => null,   // e.g. https://facebook.com/premaxautocare
            'instagram_url' => null,   // e.g. https://instagram.com/premaxautocare
            'twitter_url'   => null,
            'tiktok_url'    => null,
            'youtube_url'   => null,
            'linkedin_url'  => null,
 
            // ── Website ───────────────────────────────────────────────────
            'website_url' => null,   // e.g. https://premaxautocare.co.ke
 
            // ── Postal Address ────────────────────────────────────────────
            'po_box'       => '58230',
            'postal_code'  => '00200',
            'postal_town'  => 'Nairobi',
            'country'      => 'Kenya',
 
            // ── Physical Address ──────────────────────────────────────────
            'street_address' => 'Kiambu Road / Northern Bypass Junction',
            'landmark'       => 'Next to Glee Hotel',
            'building'       => null,
            'floor_unit'     => null,
            'area'           => 'Ruaka',
            'city'           => 'Nairobi',
            'county'         => 'Kiambu',
 
            // ── GPS Coordinates ───────────────────────────────────────────
            // Approximate coordinates for Kiambu Rd / Northern Bypass junction
            // near Glee Hotel, Ruaka — verify and update with exact pin.
            'latitude'         => -1.2103500,
            'longitude'        => 36.7941200,
            'map_zoom'         => 17,
            'google_maps_url'  => 'https://maps.app.goo.gl/premaxautocare', // replace with real short link
            'google_place_id'  => null,   // add after claiming Google Business Profile
            'what3words'       => null,   // add from what3words.com
 
            // ── Business Hours ────────────────────────────────────────────
            // Format: { "day": { "open": "HH:MM", "close": "HH:MM", "closed": bool } }
            'business_hours' => json_encode([
                'monday'    => ['open' => '07:30', 'close' => '18:00', 'closed' => false],
                'tuesday'   => ['open' => '07:30', 'close' => '18:00', 'closed' => false],
                'wednesday' => ['open' => '07:30', 'close' => '18:00', 'closed' => false],
                'thursday'  => ['open' => '07:30', 'close' => '18:00', 'closed' => false],
                'friday'    => ['open' => '07:30', 'close' => '18:00', 'closed' => false],
                'saturday'  => ['open' => '08:00', 'close' => '17:00', 'closed' => false],
                'sunday'    => ['open' => null,    'close' => null,    'closed' => true],
            ]),
 
            // ── Status ────────────────────────────────────────────────────
            'is_primary' => true,
            'is_active'  => true,
 
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
