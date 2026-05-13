<?php

namespace Database\Seeders;

use App\Models\CommentTemplate;
use Illuminate\Database\Seeder;

class CommentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name'     => 'Price Inquiry',
                'shortcut' => '/price',
                'platform' => 'all',
                'body'     => 'Hi! Thank you for your interest. Please DM us or call us directly for pricing information.',
            ],
            [
                'name'     => 'Location',
                'shortcut' => '/location',
                'platform' => 'all',
                'body'     => 'We are located in Nairobi, Kenya. Please DM us for our exact address and directions.',
            ],
            [
                'name'     => 'Operating Hours',
                'shortcut' => '/hours',
                'platform' => 'all',
                'body'     => 'We are open Monday to Saturday, 8am to 6pm. Feel free to book an appointment anytime!',
            ],
            [
                'name'     => 'Thank You',
                'shortcut' => '/thanks',
                'platform' => 'all',
                'body'     => 'Thank you so much for your kind words! We appreciate your support.',
            ],
            [
                'name'     => 'Appointment',
                'shortcut' => '/book',
                'platform' => 'all',
                'body'     => 'We would love to help! Please DM us or call us to schedule your appointment at your convenience.',
            ],
        ];

        foreach ($templates as $template) {
            CommentTemplate::firstOrCreate(
                ['shortcut' => $template['shortcut']],
                $template,
            );
        }
    }
}
