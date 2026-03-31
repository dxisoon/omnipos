<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['items', 'user'])
            ->orderByDesc('created_at');

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return response()->json($query->limit(100)->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|integer|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'discount_amount'        => 'nullable|numeric|min:0',
            'discount_type'          => 'nullable|string|in:percentage,fixed',
            'tax_amount'             => 'nullable|numeric|min:0',
            'payment_method'         => 'nullable|string|in:cash,card,simulation',
            'currency'               => 'nullable|string|size:3',
        ]);

        // We add a try-catch block here to handle the "Insufficient Stock" error gracefully
        try {
            return DB::transaction(function () use ($validated) {
                $subtotal = 0;
                $lineItems = [];

                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (!$product->is_active) {
                        throw new \Exception("Product '{$product->name}' is not active.");
                    }

                    if ($product->stock_qty < $item['quantity']) {
                        // This is what triggers the 422 response now
                        throw new \Exception("Insufficient stock for '{$product->name}'. Available: {$product->stock_qty}, Requested: {$item['quantity']}");
                    }

                    $lineSubtotal = $product->price * $item['quantity'];
                    $subtotal += $lineSubtotal;

                    $lineItems[] = [
                        'product'      => $product,
                        'quantity'     => $item['quantity'],
                        'unit_price'   => $product->price,
                        'subtotal'     => $lineSubtotal,
                    ];
                }

                $discountAmount = 0;
                if (!empty($validated['discount_amount'])) {
                    if (($validated['discount_type'] ?? 'fixed') === 'percentage') {
                        $discountAmount = round($subtotal * ($validated['discount_amount'] / 100), 2);
                    } else {
                        $discountAmount = $validated['discount_amount'];
                    }
                }

                $taxAmount   = $validated['tax_amount'] ?? 0;
                $totalAmount = $subtotal - $discountAmount + $taxAmount;

                $sale = Sale::create([
                    'user_id'         => 1,
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discountAmount,
                    'discount_type'   => $validated['discount_type'] ?? 'fixed',
                    'tax_amount'      => $taxAmount,
                    'total_amount'    => $totalAmount,
                    'payment_method'  => $validated['payment_method'] ?? 'cash',
                    'payment_status'  => 'paid',
                    'currency'        => $validated['currency'] ?? 'MYR',
                    'receipt_token'   => Str::uuid(),
                ]);

                foreach ($lineItems as $line) {
                    $product = $line['product'];
                    $sale->items()->create([
                        'product_id'   => $product->id,
                        'product_name' => $product->name,
                        'unit_price'   => $line['unit_price'],
                        'quantity'     => $line['quantity'],
                        'subtotal'     => $line['subtotal'],
                    ]);

                    $stockBefore = $product->stock_qty;
                    $product->decrement('stock_qty', $line['quantity']);

                    InventoryLog::create([
                        'product_id'   => $product->id,
                        'user_id'      => 1,
                        'change_qty'   => -$line['quantity'],
                        'stock_before' => $stockBefore,
                        'stock_after'  => $stockBefore - $line['quantity'],
                        'reason'       => 'sale',
                        'notes'        => "Sale ID #{$sale->id}",
                    ]);
                }

                return response()->json($sale->load('items'), 201);
            });
        } catch (\Exception $e) {
            // This returns the error as JSON with a 422 code instead of a 500 crash
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function show(Sale $sale)
    {
        return response()->json($sale->load(['items', 'user']));
    }

    public function update(Request $request, Sale $sale)
    {
        // Sales are mostly immutable — only status can be updated
        $validated = $request->validate([
            'payment_status' => 'required|string|in:paid,declined,pending,refunded',
        ]);

        $sale->update($validated);
        return response()->json($sale);
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return response()->json(['message' => 'Sale deleted successfully']);
    }

    public function receipt(string $token)
    {
        $sale = Sale::with(['items', 'user'])
            ->where('receipt_token', $token)
            ->firstOrFail();

        return response()->json($sale);
    }

    public function exportDaily(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $sales = Sale::with('items')
            ->whereDate('created_at', $date)
            ->get();

        if ($sales->isEmpty()) {
            return response()->json([
                'message' => "No sales found for {$date}"
            ], 404);
        }

        return Excel::download(new SalesExport($date), "sales-{$date}.xlsx");
    }
}
