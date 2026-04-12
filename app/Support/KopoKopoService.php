<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class KopoKopoService
{
    public function isConfigured(): bool
    {
        return (bool) ($this->clientId() && $this->clientSecret() && $this->tillNumber());
    }

    public function initiateIncomingPayment(array $payload): array
    {
        $response = $this->client()
            ->withToken($this->accessToken())
            ->post($this->baseUrl() . '/api/v1/incoming_payments', $payload)
            ->throw();

        return [
            'location' => $response->header('Location'),
            'body' => $response->json(),
        ];
    }

    public function paymentStatus(string $location): array
    {
        return $this->client()
            ->withToken($this->accessToken())
            ->get($location)
            ->throw()
            ->json();
    }

    public function tillNumber(): ?string
    {
        return Setting::get('kopokopo_till_number');
    }

    public function environment(): string
    {
        return Setting::get('kopokopo_environment', 'sandbox');
    }

    public function baseUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://app.kopokopo.com'
            : 'https://sandbox.kopokopo.com';
    }

    protected function accessToken(): string
    {
        return Cache::remember('kopokopo.access_token', 3300, function () {
            $response = $this->client()
                ->asForm()
                ->post($this->baseUrl() . '/oauth/token', [
                    'client_id' => $this->clientId(),
                    'client_secret' => $this->clientSecret(),
                    'grant_type' => 'client_credentials',
                ])
                ->throw()
                ->json();

            return $response['access_token'];
        });
    }

    protected function client(): PendingRequest
    {
        return Http::acceptJson()->withHeaders([
            'User-Agent' => 'premax-admin/1.0',
        ]);
    }

    protected function clientId(): ?string
    {
        return Setting::get('kopokopo_client_id');
    }

    protected function clientSecret(): ?string
    {
        return Setting::get('kopokopo_client_secret');
    }
}
