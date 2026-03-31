<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CurrencyController extends Controller
{
    public function rates()
    {
        $base = 'MYR';

        // Cache rates for 60 minutes to avoid hammering the API
        $rates = Cache::remember('currency_rates', 3600, function () use ($base) {
            $start = now();

            try {
                $response = Http::timeout(5)
                    ->get("https://open.er-api.com/v6/latest/{$base}");

                $latency = now()->diffInMilliseconds($start);

                ApiLog::create([
                    'service'       => 'exchangerate-api',
                    'endpoint'      => "/v6/latest/{$base}",
                    'response_code' => $response->status(),
                    'latency_ms'    => $latency,
                    'payload'       => ['base' => $base],
                    'response'      => $response->json(),
                    'status'        => $response->successful() ? 'success' : 'failed',
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return [
                        'base'   => $base,
                        'rates'  => [
                            'USD' => $data['rates']['USD'],
                            'SGD' => $data['rates']['SGD'],
                            'IDR' => $data['rates']['IDR'],
                            'EUR' => $data['rates']['EUR'],
                        ],
                        'source' => 'live',
                    ];
                }

            } catch (\Exception $e) {
                ApiLog::create([
                    'service'       => 'exchangerate-api',
                    'endpoint'      => "/v6/latest/{$base}",
                    'response_code' => null,
                    'latency_ms'    => null,
                    'payload'       => ['base' => $base],
                    'response'      => ['error' => $e->getMessage()],
                    'status'        => 'timeout',
                ]);
            }

            // Fallback rates if API is down
            return [
                'base'   => $base,
                'rates'  => [
                    'USD' => 0.2254,
                    'SGD' => 0.3021,
                    'IDR' => 3520.00,
                    'EUR' => 0.2076,
                ],
                'source' => 'fallback',
            ];
        });

        return response()->json($rates);
    }
}