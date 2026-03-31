<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HeldCart;
use Illuminate\Http\Request;

class HeldCartController extends Controller
{
    public function index()
    {
        return response()->json(HeldCart::orderByDesc('created_at')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label'     => 'nullable|string|max:255',
            'cart_data' => 'required|array',
            'cart_data.*.product_id' => 'required|integer',
            'cart_data.*.quantity'   => 'required|integer|min:1',
            'cart_data.*.unit_price' => 'required|numeric|min:0',
        ]);

        $cart = HeldCart::create([
            'user_id'   => 1, // will wire to auth user later
            'label'     => $validated['label'] ?? 'Held Cart ' . now()->format('H:i'),
            'cart_data' => $validated['cart_data'],
        ]);

        return response()->json($cart, 201);
    }

    public function show(HeldCart $heldCart)
    {
        return response()->json($heldCart);
    }

    public function update(Request $request, HeldCart $heldCart)
    {
        $validated = $request->validate([
            'label'     => 'nullable|string|max:255',
            'cart_data' => 'sometimes|array',
        ]);

        $heldCart->update($validated);
        return response()->json($heldCart);
    }

    public function destroy(HeldCart $heldCart)
    {
        $heldCart->delete();
        return response()->json(['message' => 'Held cart released successfully']);
    }
}