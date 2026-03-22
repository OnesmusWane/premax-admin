<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class ContactInformation extends Model
{
    use SoftDeletes;

    protected $table = 'contact_information';

    protected $fillable = [
        'label',
        'phone_primary',
        'phone_secondary',
        'phone_whatsapp',
        'email_primary',
        'email_support',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'tiktok_url',
        'youtube_url',
        'linkedin_url',
        'website_url',
        'po_box',
        'postal_code',
        'postal_town',
        'country',
        'street_address',
        'landmark',
        'building',
        'floor_unit',
        'area',
        'city',
        'county',
        'latitude',
        'longitude',
        'map_zoom',
        'google_maps_url',
        'google_place_id',
        'what3words',
        'business_hours',
        'is_primary',
        'is_active',
    ];

    protected $casts = [
        'business_hours' => 'array',
        'is_primary'     => 'boolean',
        'is_active'      => 'boolean',
        'latitude'       => 'float',
        'longitude'      => 'float',
    ];

    // ── Accessors ─────────────────────────────────────────────────────────

    /**
     * Format phone for tel: links — strips spaces and ensures + prefix.
     * Usage: $contact->phone_primary_e164  →  +254742091794
     */
    protected function phonePrimaryE164(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatE164($this->phone_primary)
        );
    }

    protected function phoneSecondaryE164(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatE164($this->phone_secondary)
        );
    }

    protected function phoneWhatsappE164(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->formatE164($this->phone_whatsapp)
        );
    }

    /**
     * Full postal address as a single string.
     * e.g.  P.O. Box 58230-00200, Nairobi, Kenya
     */
    protected function postalAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->po_box
                        ? 'P.O. Box ' . $this->po_box . ($this->postal_code ? '-' . $this->postal_code : '')
                        : null,
                    $this->postal_town,
                    $this->country,
                ]);
                return implode(', ', $parts);
            }
        );
    }

    /**
     * Short display address for the topbar.
     * e.g.  Kiambu Road / Northern Bypass Junction, Nairobi
     */
    protected function shortAddress(): Attribute
    {
        return Attribute::make(
            get: function () {
                $parts = array_filter([
                    $this->street_address,
                    $this->city,
                ]);
                return implode(', ', $parts);
            }
        );
    }

    /**
     * WhatsApp click-to-chat URL.
     */
    protected function whatsappUrl(): Attribute
    {
        return Attribute::make(
            get: function () {
                $number = $this->formatE164($this->phone_whatsapp ?? $this->phone_primary);
                return $number ? 'https://wa.me/' . ltrim($number, '+') : null;
            }
        );
    }

    /**
     * Today's opening hours as a human-readable string.
     * e.g.  Mon–Fri: 7:30am – 6:00pm
     */
    protected function todayHours(): Attribute
    {
        return Attribute::make(
            get: function () {
                $hours = $this->business_hours;
                if (! $hours) return null;

                $day = strtolower(Carbon::now()->format('l')); // e.g. "monday"

                if (! isset($hours[$day])) return null;

                $today = $hours[$day];

                if ($today['closed'] ?? false) return 'Closed today';

                $open  = $this->formatTime($today['open']  ?? null);
                $close = $this->formatTime($today['close'] ?? null);

                return ($open && $close) ? "{$open} – {$close}" : null;
            }
        );
    }

    /**
     * Returns a formatted summary of weekly hours.
     * e.g. ["Mon – Fri: 7:30am – 6:00pm", "Sat: 8:00am – 5:00pm", "Sun: Closed"]
     */
    public function formattedHours(): array
    {
        $hours = $this->business_hours;
        if (! $hours) return [];

        $lines = [];
        foreach ($hours as $day => $slot) {
            $label = ucfirst(substr($day, 0, 3)); // Mon, Tue …
            if ($slot['closed'] ?? false) {
                $lines[] = "{$label}: Closed";
            } else {
                $open  = $this->formatTime($slot['open']  ?? null);
                $close = $this->formatTime($slot['close'] ?? null);
                $lines[] = "{$label}: {$open} – {$close}";
            }
        }
        return $lines;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    private function formatE164(?string $number): ?string
    {
        if (! $number) return null;
        $clean = preg_replace('/\s+/', '', $number);
        return str_starts_with($clean, '+') ? $clean : '+' . ltrim($clean, '0');
    }

    private function formatTime(?string $time): ?string
    {
        if (! $time) return null;
        return Carbon::createFromFormat('H:i', $time)->format('g:ia'); // 7:30am
    }

    // ── Scopes ────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}