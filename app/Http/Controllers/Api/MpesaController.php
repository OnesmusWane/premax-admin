<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MpesaTransaction;
use App\Support\KopoKopoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MpesaController extends Controller
{
    public function __construct(private KopoKopoService $kopoKopo)
    {
    }

    /**
     * POST /api/admin/mpesa/stk-push
     * Sends an M-PESA collection request via KopoKopo.
     */
    public function stkPush(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'amount' => 'required|integer|min:1',
            'booking_id' => 'nullable',
            'reference' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:150',
        ]);

        if (!$this->kopoKopo->isConfigured()) {
            return response()->json([
                'message' => 'KopoKopo is not configured. Please update Settings.',
            ], 422);
        }

        $phone = $this->formatPhone($request->phone);
        $reference = $request->reference ?: ($request->booking_id ? 'BOOKING-' . $request->booking_id : 'POS-' . now()->format('YmdHis'));

        try {
            $response = $this->kopoKopo->initiateIncomingPayment([
                'payment_channel' => 'M-PESA STK Push',
                'till_number' => $this->kopoKopo->tillNumber(),
                'subscriber' => [
                    'first_name' => strtok($request->customer_name ?: 'Anonymous', ' '),
                    'last_name' => trim(str_replace(strtok($request->customer_name ?: 'Anonymous', ' '), '', $request->customer_name ?: '')) ?: 'Client',
                    'phone_number' => $phone,
                ],
                'amount' => [
                    'currency' => 'KES',
                    'value' => (int) $request->amount,
                ],
                'metadata' => [
                    'reference' => $reference,
                    'notes' => 'Premax payment collection',
                ],
                '_links' => [
                    'callback_url' => url('/api/admin/mpesa/callback'),
                ],
            ]);

            $location = $response['location'];
            $checkoutId = basename(parse_url($location, PHP_URL_PATH));

            MpesaTransaction::updateOrCreate(
                ['checkout_request_id' => $checkoutId],
                [
                    'provider' => 'kopokopo',
                    'location' => $location,
                    'internal_reference' => $reference,
                    'phone' => $phone,
                    'amount' => (int) $request->amount,
                    'status' => 'pending',
                ]
            );

            return response()->json([
                'message' => 'Payment prompt sent. Please check the customer phone.',
                'checkout_request' => $checkoutId,
            ]);
        } catch (\Throwable $e) {
            Log::error('KopoKopo STK request failed', ['message' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to send payment prompt.',
            ], 502);
        }
    }

    /**
     * POST /api/admin/mpesa/callback
     * KopoKopo webhook callback.
     */
    public function callback(Request $request)
    {
        Log::info('KopoKopo callback received', $request->all());

        $resource = $request->input('data.attributes.event.resource', []);
        $reference = $resource['reference'] ?? null;
        $status = strtolower($request->input('data.attributes.status', 'pending'));
        $phone = $resource['sender_phone_number'] ?? null;
        $amount = $resource['amount'] ?? null;
        $location = $request->input('data.links.self') ?? $request->input('data.attributes._links.self');
        $checkoutId = $reference ?: ($location ? basename(parse_url($location, PHP_URL_PATH)) : null);

        if (!$checkoutId) {
            return response()->json(['message' => 'Accepted']);
        }

        MpesaTransaction::updateOrCreate(
            ['checkout_request_id' => $checkoutId],
            [
                'provider' => 'kopokopo',
                'location' => $location,
                'mpesa_receipt_number' => $reference,
                'phone' => $phone,
                'amount' => $amount,
                'status' => $status === 'success' ? 'success' : ($status === 'failed' ? 'failed' : 'pending'),
                'result_desc' => $request->input('data.attributes.event.type'),
                'callback_payload' => $request->all(),
            ]
        );

        return response()->json(['message' => 'Accepted']);
    }

    public function checkStatus(Request $request)
    {
        $request->validate([
            'checkout_request_id' => 'required|string',
        ]);

        $tx = MpesaTransaction::where('checkout_request_id', $request->checkout_request_id)->first();

        if (!$tx) {
            return response()->json(['status' => 'pending']);
        }

        if ($tx->status === 'pending' && $tx->location && $this->kopoKopo->isConfigured()) {
            try {
                $statusPayload = $this->kopoKopo->paymentStatus($tx->location);
                $remoteStatus = strtolower($statusPayload['data']['attributes']['status'] ?? 'pending');
                $resource = $statusPayload['data']['attributes']['event']['resource'] ?? [];

                $tx->update([
                    'status' => $remoteStatus === 'success' ? 'success' : ($remoteStatus === 'failed' ? 'failed' : 'pending'),
                    'mpesa_receipt_number' => $resource['reference'] ?? $tx->mpesa_receipt_number,
                    'phone' => $resource['sender_phone_number'] ?? $tx->phone,
                    'amount' => $resource['amount'] ?? $tx->amount,
                    'callback_payload' => $statusPayload,
                ]);
            } catch (\Throwable $e) {
                Log::warning('KopoKopo payment status poll failed', ['message' => $e->getMessage()]);
            }
        }

        return response()->json([
            'status' => $tx->fresh()->status,
            'mpesa_receipt_number' => $tx->fresh()->mpesa_receipt_number,
            'amount' => $tx->fresh()->amount,
            'result_desc' => $tx->fresh()->result_desc,
        ]);
    }

    private function formatPhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        return '+' . $phone;
    }
}
