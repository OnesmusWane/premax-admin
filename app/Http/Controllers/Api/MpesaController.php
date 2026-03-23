<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Setting;

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

        $amount    = (int) $request->amount;
        $paybill = Setting::where('key', 'mpesa_paybill')->value('value');
        $env     = Setting::where('key', 'mpesa_env')->value('value') ?? 'sandbox';
        $consKey = Setting::where('key', 'mpesa_consumer_key')->value('value');
        $consSec = Setting::where('key', 'mpesa_consumer_secret')->value('value');
        if (!$paybill || !$consKey || !$consSec) {
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
                return response()->json(['message' => 'Failed to get M-Pesa token.'], 502);
            }

            $token     = $tokenRes->json('access_token');
            $timestamp = now()->format('YmdHis');
            $password  = base64_encode($paybill . $paybill . $timestamp); // shortcode+passkey+timestamp

            // 2. STK Push request
            $stkRes = Http::withToken($token)
                ->post("{$baseUrl}/mpesa/stkpush/v1/processrequest", [
                    'BusinessShortCode' => $paybill,
                    'Password'          => $password,
                    'Timestamp'         => $timestamp,
                    'TransactionType'   => 'CustomerPayBillOnline',
                    'Amount'            => $amount,
                    'PartyA'            => $phone,
                    'PartyB'            => $paybill,
                    'PhoneNumber'       => $phone,
                    'CallBackURL'       => url('/api/admin/mpesa/callback'),
                    'AccountReference'  => 'PremaxAutocare',
                    'TransactionDesc'   => 'Service Payment',
                ]);

            if ($stkRes->successful()) {
                return response()->json([
                    'message'          => 'STK push sent.',
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
        Log::info('M-Pesa callback', $request->all());

        $body    = $request->input('Body.stkCallback');
        $code    = $body['ResultCode'] ?? null;
        $checkId = $body['CheckoutRequestID'] ?? null;

        if ($code === 0) {
            // Payment successful
            $items  = collect($body['CallbackMetadata']['Item'] ?? []);
            $amount = $items->firstWhere('Name', 'Amount')['Value'] ?? null;
            $mpesaRef = $items->firstWhere('Name', 'MpesaReceiptNumber')['Value'] ?? null;
            $phone  = $items->firstWhere('Name', 'PhoneNumber')['Value'] ?? null;

            Log::info("M-Pesa payment confirmed: {$mpesaRef} — KES {$amount} from {$phone}");
            // TODO: auto-match to invoice by CheckoutRequestID if needed
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}