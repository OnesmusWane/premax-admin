<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffMembersSeeder extends Seeder
{
    public function run(): void
    {
        $now    = Carbon::now();
        $colors = [
            '#DC2626', // red   (brand)
            '#1D4ED8', // blue
            '#047857', // green
            '#B45309', // amber
            '#7C3AED', // purple
            '#0891B2', // cyan
            '#9D174D', // rose
            '#4B5563', // gray
        ];

        $staff = [
            [
                'name'         => 'James Mwangi',
                'role'         => 'Founder & Lead Technician',
                'bio'          => 'With over 15 years of experience in automotive care, James founded Premax Autocare with a vision to deliver world-class service to every customer.',
                'avatar_color' => $colors[0],
                'sort_order'   => 1,
            ],
            [
                'name'         => 'Grace Akinyi',
                'role'         => 'Head of Detailing',
                'bio'          => 'Grace leads our detailing team with precision and passion, ensuring every vehicle leaves in showroom condition.',
                'avatar_color' => $colors[1],
                'sort_order'   => 2,
            ],
            [
                'name'         => 'Brian Otieno',
                'role'         => 'Mechanical Specialist',
                'bio'          => 'Brian handles all mechanical and diagnostic work, from engine repairs to suspension overhauls, with meticulous attention to detail.',
                'avatar_color' => $colors[2],
                'sort_order'   => 3,
            ],
            [
                'name'         => 'Peter Kariuki',
                'role'         => 'Wheel Alignment & Tyre Technician',
                'bio'          => 'Peter is our go-to expert for all tyre services, 3D alignment, and wheel balancing, ensuring every vehicle drives straight and smooth.',
                'avatar_color' => $colors[3],
                'sort_order'   => 4,
            ],
            [
                'name'         => 'Amina Hassan',
                'role'         => 'Customer Relations Manager',
                'bio'          => 'Amina ensures every customer interaction — from booking to handover — is seamless, professional, and exceeds expectations.',
                'avatar_color' => $colors[4],
                'sort_order'   => 5,
            ],
            [
                'name'         => 'Samuel Ndung\'u',
                'role'         => 'Auto Electrician & Diagnostics',
                'bio'          => 'Samuel specialises in vehicle diagnostics, wiring repairs, and electrical systems, solving even the most complex fault codes.',
                'avatar_color' => $colors[5],
                'sort_order'   => 6,
            ],
        ];

        $rows = array_map(fn ($s) => array_merge([
            'initials'        => null,    // auto-derived from name
            'avatar_url'      => null,
            'email'           => null,
            'phone'           => null,
            'show_on_website' => true,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $s), $staff);

        DB::table('staff_members')->insert($rows);
    }
}