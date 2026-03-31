<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\InventoryLog;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $product->is_low_stock = $product->isLowStock();
                return $product;
            });

        return response()->json($products);
    }

    public function adjust(Request $request, Product $product)
    {
        $validated = $request->validate([
            'change_qty' => 'required|integer',
            'reason'     => 'required|string|in:manual_adjustment,restock,damage,correction',
            'notes'      => 'nullable|string',
        ]);

        $stockBefore = $product->stock_qty;
        $newStock    = $stockBefore + $validated['change_qty'];

        if ($newStock < 0) {
            return response()->json([
                'message' => 'Insufficient stock. Cannot reduce below zero.'
            ], 422);
        }

        $product->update(['stock_qty' => $newStock]);

        $log = InventoryLog::create([
            'product_id'   => $product->id,
            'user_id'      => 1, // will wire to auth user later
            'change_qty'   => $validated['change_qty'],
            'stock_before' => $stockBefore,
            'stock_after'  => $newStock,
            'reason'       => $validated['reason'],
            'notes'        => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'message'      => 'Stock adjusted successfully',
            'stock_before' => $stockBefore,
            'stock_after'  => $newStock,
            'log'          => $log,
        ]);
    }

    public function logs()
    {
        $logs = InventoryLog::with(['product', 'user'])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        return response()->json($logs);
    }

    public function lowStock()
    {
        $products = Product::where('is_active', true)
            ->whereColumn('stock_qty', '<', 'low_stock_threshold')
            ->with('category')
            ->orderBy('stock_qty')
            ->get();

        return response()->json([
            'count'    => $products->count(),
            'products' => $products,
        ]);
    }
}