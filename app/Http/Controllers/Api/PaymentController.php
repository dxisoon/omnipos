<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Test cards for simulation
    const APPROVED_CARDS = [
        '4111111111111111', // Visa test
        '5500005555555559', // Mastercard test
        '374251018720022',  // Amex test
    ];

    const DECLINED_CARDS = [
        '4000000000000002', // Always decline
        '4000000000009995', // Insufficient funds
    ];

    public function process(Request $request)
    {
        $validated = $request->validate([
            'card_number' => 'required|string',
            'amount'      => 'required|numeric|min:0.01',
            'currency'    => 'required|string|size:3',
        ]);

        // Strip spaces/dashes from card number
        $card = preg_replace('/[\s\-]/', '', $validated['card_number']);

        // Validate card is numeric only
        if (!ctype_digit($card)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid card number format.',
                'code'    => 422,
            ], 422);
        }

        $start = now();

        // Simulate processing delay (bank terminal feel)
        usleep(800000); // 0.8 seconds

        $latency = now()->diffInMilliseconds($start);

        // Determine outcome
        if (in_array($card, self::APPROVED_CARDS)) {
            $result = [
                'status'           => 'approved',
                'message'          => 'Payment approved.',
                'transaction_id'   => strtoupper(uniqid('TXN-')),
                'amount'           => $validated['amount'],
                'currency'         => $validated['currency'],
                'code'             => 200,
            ];
            $httpCode = 200;

        } elseif (in_array($card, self::DECLINED_CARDS)) {
            $result = [
                'status'  => 'declined',
                'message' => 'Payment declined. Insufficient funds.',
                'code'    => 402,
            ];
            $httpCode = 402;

        } else {
            // Unknown card — decline by default
            $result = [
                'status'  => 'declined',
                'message' => 'Card not recognised. Use a test card number.',
                'code'    => 402,
            ];
            $httpCode = 402;
        }

        // Log the transaction
        ApiLog::create([
            'service'       => 'payment-simulation',
            'endpoint'      => '/api/payment/process',
            'response_code' => $httpCode,
            'latency_ms'    => $latency,
            'payload'       => [
                'card_last4' => substr($card, -4),
                'amount'     => $validated['amount'],
                'currency'   => $validated['currency'],
            ],
            'response' => $result,
            'status'   => $result['status'] === 'approved' ? 'success' : 'failed',
        ]);

        return response()->json($result, $httpCode);
    }
}