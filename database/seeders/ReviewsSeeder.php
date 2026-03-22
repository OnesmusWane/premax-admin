<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReviewsSeeder extends Seeder
{
    /**
     * Seed sample customer reviews for Premax Autocare & Diagnostic Services.
     * Reviews are sourced from the website testimonials section.
     */
    public function run(): void
    {
        $now = Carbon::now();

        // Avatar colors — rotate through brand-adjacent palette
        $colors = [
            '#DC2626', // red   (brand)
            '#B45309', // amber
            '#1D4ED8', // blue
            '#047857', // green
            '#7C3AED', // purple
            '#0891B2', // cyan
            '#9D174D', // rose
            '#4B5563', // gray
        ];

        $reviews = [
            // ── Screenshot reviews ────────────────────────────────────────
            [
                'reviewer_name'         => 'David Ochieng',
                'reviewer_initials'     => 'DO',
                'reviewer_avatar_color' => $colors[0],
                'rating'                => 5,
                'body'                  => 'Best car wash in Nairobi. They pay attention to every detail and my car always looks brand new after a full detailing session.',
                'source'                => 'website',
                'is_verified_customer'  => true,
                'is_featured'           => true,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(14),
            ],
            [
                'reviewer_name'         => 'Sarah Wanjiku',
                'reviewer_initials'     => 'SW',
                'reviewer_avatar_color' => $colors[1],
                'rating'                => 5,
                'body'                  => 'Very professional and fast. I love that I can book online and not have to wait in line. The waiting area is also very clean.',
                'source'                => 'website',
                'is_verified_customer'  => true,
                'is_featured'           => true,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(21),
            ],
            [
                'reviewer_name'         => 'Kamau Njoroge',
                'reviewer_initials'     => 'KN',
                'reviewer_avatar_color' => $colors[2],
                'rating'                => 4,
                'body'                  => 'Great service for engine wash. They covered all sensitive parts and did a thorough job. Will definitely be coming back.',
                'source'                => 'website',
                'is_verified_customer'  => true,
                'is_featured'           => true,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(30),
            ],

            // ── Additional sample reviews ─────────────────────────────────
            [
                'reviewer_name'         => 'Joyce Muthoni',
                'reviewer_initials'     => 'JM',
                'reviewer_avatar_color' => $colors[3],
                'rating'                => 5,
                'body'                  => 'Had my wheel alignment and balancing done here. The difference is night and day — no more steering vibration. Very fair pricing too.',
                'source'                => 'google',
                'is_verified_customer'  => true,
                'is_featured'           => false,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(7),
            ],
            [
                'reviewer_name'         => 'Brian Otieno',
                'reviewer_initials'     => 'BO',
                'reviewer_avatar_color' => $colors[4],
                'rating'                => 5,
                'body'                  => 'Got my oil change and full diagnostic done in under an hour. The team explained every fault code clearly. Highly recommend for any car issues.',
                'source'                => 'google',
                'is_verified_customer'  => true,
                'is_featured'           => false,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(45),
            ],
            [
                'reviewer_name'         => 'Amina Hassan',
                'reviewer_initials'     => 'AH',
                'reviewer_avatar_color' => $colors[5],
                'rating'                => 5,
                'body'                  => 'Replaced my car battery and they tested the alternator as well at no extra charge. Very honest and transparent with costs.',
                'source'                => 'facebook',
                'is_verified_customer'  => true,
                'is_featured'           => false,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(60),
            ],
            [
                'reviewer_name'         => 'Peter Kariuki',
                'reviewer_initials'     => 'PK',
                'reviewer_avatar_color' => $colors[6],
                'rating'                => 4,
                'body'                  => 'Had two punctures repaired and tyres rotated. Quick service, reasonable price. The location near the Northern Bypass is very convenient.',
                'source'                => 'walk_in',
                'is_verified_customer'  => true,
                'is_featured'           => false,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(10),
            ],
            [
                'reviewer_name'         => 'Grace Waweru',
                'reviewer_initials'     => 'GW',
                'reviewer_avatar_color' => $colors[7],
                'rating'                => 5,
                'body'                  => 'Excellent panel beating work on my bumper. You cannot even tell there was ever a dent. The paint match is perfect.',
                'source'                => 'whatsapp',
                'is_verified_customer'  => true,
                'is_featured'           => false,
                'show_on_website'       => true,
                'status'                => 'approved',
                'reviewed_at'           => Carbon::now()->subDays(90),
            ],
        ];

        $rows = array_map(fn ($r) => array_merge([
            'reviewer_email'         => null,
            'reviewer_phone'         => null,
            'reviewer_avatar_url'    => null,
            'service_id'             => null,
            'service_category_id'    => null,
            'source_url'             => null,
            'source_review_id'       => null,
            'verification_reference' => null,
            'moderation_note'        => null,
            'owner_response'         => null,
            'owner_responded_at'     => null,
            'created_at'             => $now,
            'updated_at'             => $now,
        ], $r), $reviews);

        DB::table('reviews')->insert($rows);
    }
}