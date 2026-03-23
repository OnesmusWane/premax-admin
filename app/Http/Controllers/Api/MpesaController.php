<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;
use App\Models\MpesaTransaction;
class MpesaController extends Controller
{
    /**
     * POST /api/admin/mpesa/stk-push
     * Sends an STK push to the customer's phone.
     */
    public function stkPush(Request $request)
{
    $request->validate([
        'phone'  => 'required|string',
        'amount' => 'required|integer|min:1',
    ]);

    // Format phone to 254XXXXXXXXX
    $phone = preg_replace('/\s+/', '', $request->phone);
    $phone = ltrim($phone, '+');
    if (str_starts_with($phone, '0')) {
        $phone = '254' . substr($phone, 1);
    }

    $amount  = (int) $request->amount;
    $env     = Setting::where('key', 'mpesa_env')->value('value') ?? 'sandbox';
    $consKey = Setting::where('key', 'mpesa_consumer_key')->value('value');
    $consSec = Setting::where('key', 'mpesa_consumer_secret')->value('value');

    // Sandbox uses fixed shortcode & passkey; production uses your real paybill & passkey
    if ($env === 'production') {
        $shortcode = Setting::where('key', 'mpesa_paybill')->value('value');
        $passkey   = Setting::where('key', 'mpesa_passkey')->value('value');
    } else {
        $shortcode = '174379';
        $passkey   = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
    }

    if (!$shortcode || !$consKey || !$consSec) {
        return response()->json([
            'message' => 'M-Pesa is not configured. Please update Settings.',
        ], 422);
    }

    $baseUrl = $env === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke';

    try {
        // 1. Get access token
        $tokenRes = Http::withBasicAuth($consKey, $consSec)
            ->get("{$baseUrl}/oauth/v1/generate?grant_type=client_credentials");

        if (!$tokenRes->successful()) {
            Log::error('M-Pesa token error', $tokenRes->json());
            return response()->json(['message' => 'Failed to get M-Pesa token.'], 502);
        }

        $token = $tokenRes->json('access_token');

        // 2. Build password: base64(shortcode + passkey + timestamp)
        $timestamp = now()->format('YmdHis');
        $password  = base64_encode($shortcode . $passkey . $timestamp);

        // 3. STK Push request
        $stkRes = Http::withToken($token)
            ->post("{$baseUrl}/mpesa/stkpush/v1/processrequest", [
                'BusinessShortCode' => $shortcode,
                'Password'          => $password,
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => $amount,
                'PartyA'            => $phone,
                'PartyB'            => $shortcode,
                'PhoneNumber'       => $phone,
                'CallBackURL'       => url('/api/admin/mpesa/callback'),
                'AccountReference'  => 'PremaxAutocare',
                'TransactionDesc'   => 'Service Payment',
            ]);

        if ($stkRes->successful()) {
            $checkoutId = $stkRes->json('CheckoutRequestID');

            // Save immediately as pending so polling can find it
            MpesaTransaction::updateOrCreate(
                ['checkout_request_id' => $checkoutId],
                [
                    'phone'  => $phone,
                    'amount' => $amount,
                    'status' => 'pending',
                ]
            );
            return response()->json([
                'message'          => 'STK push sent. Please check your phone.',
                'checkout_request' => $stkRes->json('CheckoutRequestID'),
            ]);
        }

        Log::error('M-Pesa STK push failed', $stkRes->json());
        return response()->json([
            'message' => $stkRes->json('errorMessage') ?? 'STK push failed.',
        ], 502);

    } catch (\Exception $e) {
        Log::error('M-Pesa exception: ' . $e->getMessage());
        return response()->json(['message' => 'M-Pesa request failed.'], 500);
    }
}

    /**
     * POST /api/admin/mpesa/callback
     * Safaricom calls this after payment.
     * Logs the result — you can extend to auto-confirm invoices.
     */
    public function callback(Request $request)
    {
        Log::info('M-Pesa callback received', $request->all());

        $body    = $request->input('Body.stkCallback');
        $code    = $body['ResultCode'] ?? null;
        $checkId = $body['CheckoutRequestID'] ?? null;

        if (!$checkId) {
            Log::warning('M-Pesa callback missing CheckoutRequestID');
            return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
        }

        if ($code === 0) {
            // ── Payment successful ──────────────────────────────────────────
            $items    = collect($body['CallbackMetadata']['Item'] ?? []);
            $amount   = $items->firstWhere('Name', 'Amount')['Value']             ?? null;
            $mpesaRef = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
            $phone    = (string) ($items->firstWhere('Name', 'PhoneNumber')['Value'] ?? null);

            Log::info("M-Pesa payment confirmed: {$mpesaRef} — KES {$amount} from {$phone}");

            // Save to mpesa_transactions table
            MpesaTransaction::updateOrCreate(
                ['checkout_request_id' => $checkId],
                [
                    'mpesa_receipt_number' => $mpesaRef,
                    'phone'                => $phone,
                    'amount'               => $amount,
                    'status'               => 'success',
                    'result_code'          => $code,
                    'result_desc'          => $body['ResultDesc'] ?? 'Success',
                ]
            );

        } else {
            // ── Payment failed or cancelled ─────────────────────────────────
            $desc = $body['ResultDesc'] ?? 'Failed';
            Log::warning("M-Pesa payment failed: {$desc} (CheckoutRequestID: {$checkId})");

            MpesaTransaction::updateOrCreate(
                ['checkout_request_id' => $checkId],
                [
                    'status'      => 'failed',
                    'result_code' => $code,
                    'result_desc' => $desc,
                ]
            );
        }

        // Always respond with 0 — tells Safaricom you received the callback
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
        public function checkStatus(Request $request)
        {
            Log::info('status', $request->checkout_request_id);
            $request->validate([
                'checkout_request_id' => 'required|string',
            ]);

            $tx = MpesaTransaction::where('checkout_request_id', $request->checkout_request_id)->first();

            if (!$tx) {
                return response()->json(['status' => 'pending']);
            }
            Log::info('status', $tx);

            return response()->json([
                'status'               => $tx->status,
                'mpesa_receipt_number' => $tx->mpesa_receipt_number,
                'amount'               => $tx->amount,
                'result_desc'          => $tx->result_desc,
            ]);
        }
}