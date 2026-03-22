<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $now = Carbon::now();
 
        // ─────────────────────────────────────────────────────────────────────
        // CATEGORIES
        // ─────────────────────────────────────────────────────────────────────
        $categories = [
            [
                'name'        => 'Tyre Services',
                'slug'        => 'tyre-services',
                'description' => 'Tyre fitting, rotation, balancing, alignment and puncture repairs.',
                'icon'        => 'circle-dot',          // Lucide
                'color'       => '#DC2626',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Wheel Alignment',
                'slug'        => 'wheel-alignment',
                'description' => '3D computerised wheel alignment for all vehicle types.',
                'icon'        => 'gauge',
                'color'       => '#DC2626',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Lubes & Oil Change',
                'slug'        => 'lubes-oil-change',
                'description' => 'Engine oil, filter, transmission, brake and differential fluid services.',
                'icon'        => 'droplets',
                'color'       => '#B45309',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Suspension & Steering',
                'slug'        => 'suspension-steering',
                'description' => 'Shock absorbers, springs, bushings, tie rods and steering components.',
                'icon'        => 'car',
                'color'       => '#1D4ED8',
                'sort_order'  => 4,
            ],
            [
                'name'        => 'Greasing & Rivetting',
                'slug'        => 'greasing-rivetting',
                'description' => 'Chassis greasing, body riveting and undercarriage maintenance.',
                'icon'        => 'wrench',
                'color'       => '#4B5563',
                'sort_order'  => 5,
            ],
            [
                'name'        => 'Spare Parts',
                'slug'        => 'spare-parts',
                'description' => 'Genuine and quality aftermarket spare parts supply and fitting.',
                'icon'        => 'package',
                'color'       => '#047857',
                'sort_order'  => 6,
            ],
            [
                'name'        => 'Car Batteries',
                'slug'        => 'car-batteries',
                'description' => 'Battery testing, replacement and charging services.',
                'icon'        => 'battery-charging',
                'color'       => '#7C3AED',
                'sort_order'  => 7,
            ],
            [
                'name'        => 'Buffing & Detailing',
                'slug'        => 'buffing-detailing',
                'description' => 'Machine polishing, paint correction and exterior detailing.',
                'icon'        => 'sparkles',
                'color'       => '#0891B2',
                'sort_order'  => 8,
            ],
            [
                'name'        => 'Diagnostics & Electrical',
                'slug'        => 'diagnostics-electrical',
                'description' => 'OBD diagnostics, fault-code clearing, auto electrical and wiring repairs.',
                'icon'        => 'cpu',
                'color'       => '#DC2626',
                'sort_order'  => 9,
            ],
            [
                'name'        => 'Panel Beating',
                'slug'        => 'panel-beating',
                'description' => 'Accident repairs, dent removal, panel straightening and painting.',
                'icon'        => 'hammer',
                'color'       => '#9D174D',
                'sort_order'  => 10,
            ],
        ];
 
        DB::table('service_categories')->insert(
            array_map(fn($c) => array_merge($c, [
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]), $categories)
        );
 
        // Fetch inserted IDs keyed by slug for easy reference below
        $catId = DB::table('service_categories')
            ->pluck('id', 'slug')
            ->all();
 
        // ─────────────────────────────────────────────────────────────────────
        // SERVICES
        // price_from / price_to are in KES
        // duration_minutes is estimated workshop time
        // ─────────────────────────────────────────────────────────────────────
        $services = [
 
            // ── Tyre Services ──────────────────────────────────────────────
            [
                'category'         => 'tyre-services',
                'name'             => 'Tyre Fitting',
                'description'      => 'Removal of old tyre and fitting of new tyre onto the rim.',
                'icon'             => 'circle-dot',
                'price_from'       => 200,
                'price_to'         => 500,
                'duration_minutes' => 20,
                'is_popular'       => true,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'tyre-services',
                'name'             => 'Tyre Rotation',
                'description'      => 'Rotate tyres to ensure even wear and extend tyre life.',
                'icon'             => 'rotate-cw',
                'price_from'       => 500,
                'price_to'         => null,
                'duration_minutes' => 30,
                'is_popular'       => true,
                'sort_order'       => 2,
            ],
            [
                'category'         => 'tyre-services',
                'name'             => 'Puncture Repair',
                'description'      => 'Professional plug or patch repair for tubeless tyres.',
                'icon'             => 'circle-dot',
                'price_from'       => 300,
                'price_to'         => null,
                'duration_minutes' => 20,
                'is_popular'       => true,
                'sort_order'       => 3,
            ],
            [
                'category'         => 'tyre-services',
                'name'             => 'Tyre Pressure Check & Inflation',
                'description'      => 'Check and inflate all four tyres to manufacturer specification.',
                'icon'             => 'gauge',
                'price_from'       => 100,
                'price_to'         => null,
                'duration_minutes' => 10,
                'is_popular'       => false,
                'sort_order'       => 4,
            ],
 
            // ── Wheel Alignment ────────────────────────────────────────────
            [
                'category'         => 'wheel-alignment',
                'name'             => 'Wheel Balancing',
                'description'      => 'Computerised wheel balancing for a smooth, vibration-free ride.',
                'icon'             => 'gauge',
                'price_from'       => 800,
                'price_to'         => 1200,
                'duration_minutes' => 45,
                'is_popular'       => true,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'wheel-alignment',
                'name'             => '3D Wheel Alignment',
                'description'      => 'Full 3D computerised four-wheel alignment to OEM specifications.',
                'icon'             => 'scan',
                'price_from'       => 2000,
                'price_to'         => 3500,
                'duration_minutes' => 60,
                'is_popular'       => true,
                'sort_order'       => 2,
            ],
 
            // ── Lubes & Oil Change ─────────────────────────────────────────
            [
                'category'         => 'lubes-oil-change',
                'name'             => 'Engine Oil & Filter Change',
                'description'      => 'Drain old oil, replace oil filter and refill with fresh engine oil.',
                'icon'             => 'droplets',
                'price_from'       => 1500,
                'price_to'         => 4000,
                'duration_minutes' => 30,
                'is_popular'       => true,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'lubes-oil-change',
                'name'             => 'Gearbox / Transmission Oil Change',
                'description'      => 'Flush and replace automatic or manual transmission fluid.',
                'icon'             => 'settings',
                'price_from'       => 3000,
                'price_to'         => 6000,
                'duration_minutes' => 60,
                'is_popular'       => false,
                'sort_order'       => 2,
            ],
            [
                'category'         => 'lubes-oil-change',
                'name'             => 'Brake Fluid Change',
                'description'      => 'Bleed and replace brake fluid to maintain braking performance.',
                'icon'             => 'shield',
                'price_from'       => 1000,
                'price_to'         => 2000,
                'duration_minutes' => 30,
                'is_popular'       => false,
                'sort_order'       => 3,
            ],
            [
                'category'         => 'lubes-oil-change',
                'name'             => 'Differential Oil Change',
                'description'      => 'Replace front and/or rear differential oil.',
                'icon'             => 'cog',
                'price_from'       => 2000,
                'price_to'         => 4000,
                'duration_minutes' => 45,
                'is_popular'       => false,
                'sort_order'       => 4,
            ],
 
            // ── Suspension & Steering ──────────────────────────────────────
            [
                'category'         => 'suspension-steering',
                'name'             => 'Shock Absorber Replacement',
                'description'      => 'Replace worn shock absorbers (per unit) for a smoother ride.',
                'icon'             => 'car',
                'price_from'       => 3000,
                'price_to'         => 8000,
                'duration_minutes' => 90,
                'is_popular'       => true,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'suspension-steering',
                'name'             => 'Suspension Bushing Replacement',
                'description'      => 'Replace control arm, sway bar or strut mount bushings.',
                'icon'             => 'wrench',
                'price_from'       => 1500,
                'price_to'         => 5000,
                'duration_minutes' => 60,
                'is_popular'       => false,
                'sort_order'       => 2,
            ],
            [
                'category'         => 'suspension-steering',
                'name'             => 'Tie Rod End Replacement',
                'description'      => 'Replace inner or outer tie rod ends to restore steering precision.',
                'icon'             => 'move-horizontal',
                'price_from'       => 2000,
                'price_to'         => 5000,
                'duration_minutes' => 60,
                'is_popular'       => false,
                'sort_order'       => 3,
            ],
            [
                'category'         => 'suspension-steering',
                'name'             => 'Ball Joint Replacement',
                'description'      => 'Replace upper or lower ball joints (per unit).',
                'icon'             => 'circle',
                'price_from'       => 2500,
                'price_to'         => 6000,
                'duration_minutes' => 75,
                'is_popular'       => false,
                'sort_order'       => 4,
            ],
 
            // ── Greasing & Rivetting ───────────────────────────────────────
            [
                'category'         => 'greasing-rivetting',
                'name'             => 'Full Chassis Greasing',
                'description'      => 'Lubricate all grease nipples and undercarriage joints.',
                'icon'             => 'droplet',
                'price_from'       => 500,
                'price_to'         => 1000,
                'duration_minutes' => 30,
                'is_popular'       => false,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'greasing-rivetting',
                'name'             => 'Body Rivetting',
                'description'      => 'Secure loose body panels and underbody components with rivets.',
                'icon'             => 'anchor',
                'price_from'       => 500,
                'price_to'         => 2000,
                'duration_minutes' => 45,
                'is_popular'       => false,
                'sort_order'       => 2,
            ],
 
            // ── Spare Parts ────────────────────────────────────────────────
            [
                'category'         => 'spare-parts',
                'name'             => 'Brake Pads Supply & Fitting',
                'description'      => 'Supply and fit quality front or rear brake pads.',
                'icon'             => 'shield',
                'price_from'       => 3000,
                'price_to'         => 8000,
                'duration_minutes' => 45,
                'is_popular'       => true,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'spare-parts',
                'name'             => 'Brake Disc Supply & Fitting',
                'description'      => 'Supply and fit brake discs (rotors) per axle.',
                'icon'             => 'circle-dot',
                'price_from'       => 5000,
                'price_to'         => 15000,
                'duration_minutes' => 60,
                'is_popular'       => false,
                'sort_order'       => 2,
            ],
            [
                'category'         => 'spare-parts',
                'name'             => 'Air Filter Replacement',
                'description'      => 'Supply and fit a new engine air filter.',
                'icon'             => 'wind',
                'price_from'       => 800,
                'price_to'         => 2500,
                'duration_minutes' => 15,
                'is_popular'       => false,
                'sort_order'       => 3,
            ],
            [
                'category'         => 'spare-parts',
                'name'             => 'Spark Plug Replacement',
                'description'      => 'Supply and fit a full set of spark plugs.',
                'icon'             => 'zap',
                'price_from'       => 2000,
                'price_to'         => 6000,
                'duration_minutes' => 30,
                'is_popular'       => false,
                'sort_order'       => 4,
            ],
 
            // ── Car Batteries ──────────────────────────────────────────────
            [
                'category'         => 'car-batteries',
                'name'             => 'Battery Testing',
                'description'      => 'Load-test battery to determine health and remaining capacity.',
                'icon'             => 'battery',
                'price_from'       => 200,
                'price_to'         => null,
                'duration_minutes' => 15,
                'is_popular'       => false,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'car-batteries',
                'name'             => 'Battery Supply & Replacement',
                'description'      => 'Supply and fit a new car battery matched to vehicle specs.',
                'icon'             => 'battery-charging',
                'price_from'       => 7000,
                'price_to'         => 18000,
                'duration_minutes' => 20,
                'is_popular'       => true,
                'sort_order'       => 2,
            ],
            [
                'category'         => 'car-batteries',
                'name'             => 'Battery Jump Start',
                'description'      => 'Jump start a flat battery to get the vehicle running.',
                'icon'             => 'zap',
                'price_from'       => 300,
                'price_to'         => null,
                'duration_minutes' => 15,
                'is_popular'       => false,
                'sort_order'       => 3,
            ],
 
            // ── Buffing & Detailing ────────────────────────────────────────
            [
                'category'         => 'buffing-detailing',
                'name'             => 'Machine Polishing / Buffing',
                'description'      => 'Remove swirl marks, light scratches and oxidation by machine polishing.',
                'icon'             => 'sparkles',
                'price_from'       => 3000,
                'price_to'         => 8000,
                'duration_minutes' => 120,
                'is_popular'       => true,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'buffing-detailing',
                'name'             => 'Full Exterior Detailing',
                'description'      => 'Wash, clay bar, polish and wax for a showroom finish.',
                'icon'             => 'star',
                'price_from'       => 5000,
                'price_to'         => 12000,
                'duration_minutes' => 180,
                'is_popular'       => false,
                'sort_order'       => 2,
            ],
 
            // ── Diagnostics & Electrical ───────────────────────────────────
            [
                'category'         => 'diagnostics-electrical',
                'name'             => 'OBD Diagnostic Scan',
                'description'      => 'Full vehicle scan to read and interpret fault codes.',
                'icon'             => 'cpu',
                'price_from'       => 1000,
                'price_to'         => 2000,
                'duration_minutes' => 30,
                'is_popular'       => true,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'diagnostics-electrical',
                'name'             => 'Fault Code Clearing',
                'description'      => 'Clear stored fault codes and reset warning lights after repair.',
                'icon'             => 'refresh-cw',
                'price_from'       => 500,
                'price_to'         => null,
                'duration_minutes' => 15,
                'is_popular'       => false,
                'sort_order'       => 2,
            ],
            [
                'category'         => 'diagnostics-electrical',
                'name'             => 'Auto Electrical Wiring Repair',
                'description'      => 'Diagnose and repair short circuits, broken wiring and electrical faults.',
                'icon'             => 'zap',
                'price_from'       => 1500,
                'price_to'         => 10000,
                'duration_minutes' => 90,
                'is_popular'       => false,
                'sort_order'       => 3,
            ],
            [
                'category'         => 'diagnostics-electrical',
                'name'             => 'Alternator Testing & Replacement',
                'description'      => 'Test charging system and replace alternator if faulty.',
                'icon'             => 'battery-charging',
                'price_from'       => 5000,
                'price_to'         => 15000,
                'duration_minutes' => 90,
                'is_popular'       => false,
                'sort_order'       => 4,
            ],
 
            // ── Panel Beating ──────────────────────────────────────────────
            [
                'category'         => 'panel-beating',
                'name'             => 'Dent Removal (Minor)',
                'description'      => 'Paintless dent removal for small dings and minor dents.',
                'icon'             => 'hammer',
                'price_from'       => 1500,
                'price_to'         => 5000,
                'duration_minutes' => 60,
                'is_popular'       => false,
                'sort_order'       => 1,
            ],
            [
                'category'         => 'panel-beating',
                'name'             => 'Panel Straightening & Repainting',
                'description'      => 'Straighten damaged panels, fill, prime and repaint to match.',
                'icon'             => 'paintbrush',
                'price_from'       => 5000,
                'price_to'         => 25000,
                'duration_minutes' => 480,
                'is_popular'       => false,
                'sort_order'       => 2,
            ],
            [
                'category'         => 'panel-beating',
                'name'             => 'Full Accident Repair',
                'description'      => 'Comprehensive structural and cosmetic accident repair with respray.',
                'icon'             => 'shield-check',
                'price_from'       => 20000,
                'price_to'         => null,
                'duration_minutes' => null,
                'is_popular'       => false,
                'sort_order'       => 3,
            ],
        ];
 
        // Build rows and insert
        $rows = [];
        foreach ($services as $s) {
            $rows[] = [
                'service_category_id' => $catId[$s['category']],
                'name'                => $s['name'],
                'slug'                => Str::slug($s['name']),
                'description'         => $s['description'],
                'icon'                => $s['icon'],
                'price_from'          => $s['price_from'],
                'price_to'            => $s['price_to'],
                'price_is_estimate'   => true,
                'duration_minutes'    => $s['duration_minutes'],
                'is_popular'          => $s['is_popular'],
                'is_active'           => true,
                'sort_order'          => $s['sort_order'],
                'created_at'          => $now,
                'updated_at'          => $now,
            ];
        }
 
        DB::table('services')->insert($rows);
    }
}
