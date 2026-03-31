<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'barcode'             => 'required|string|regex:/^[a-zA-Z0-9\-]+$/|unique:products,barcode',
            'name'                => 'required|string|max:255',
            'price'               => 'required|numeric|min:0',
            'stock_qty'           => 'required|integer|min:0',
            'category_id'         => 'nullable|exists:categories,id',
            'description'         => 'nullable|string',
            'cost_price'          => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json($product->load('category'), 201);
    }

    public function show(Product $product)
    {
        $product->is_low_stock = $product->isLowStock();
        return response()->json($product->load('category'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'barcode'             => 'sometimes|string|regex:/^[a-zA-Z0-9\-]+$/|unique:products,barcode,' . $product->id,
            'name'                => 'sometimes|string|max:255',
            'price'               => 'sometimes|numeric|min:0',
            'stock_qty'           => 'sometimes|integer|min:0',
            'category_id'         => 'nullable|exists:categories,id',
            'description'         => 'nullable|string',
            'cost_price'          => 'nullable|numeric|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_active'           => 'sometimes|boolean',
        ]);

        $product->update($validated);

        return response()->json($product->load('category'));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->json(['message' => 'Product deleted successfully']);
    }

    public function findByBarcode(string $barcode)
    {
        // Strict barcode validation — no symbols or plain text
        if (!preg_match('/^[a-zA-Z0-9\-]+$/', $barcode)) {
            return response()->json([
                'message' => 'Invalid barcode format.'
            ], 422);
        }

        $product = Product::with('category')
            ->where('barcode', $barcode)
            ->where('is_active', true)
            ->first();

        if (!$product) {
            return response()->json([
                'found'   => false,
                'message' => 'Product not found. Would you like to register it?'
            ], 404);
        }

        $product->is_low_stock = $product->isLowStock();

        return response()->json([
            'found'   => true,
            'product' => $product,
        ]);
    }
}