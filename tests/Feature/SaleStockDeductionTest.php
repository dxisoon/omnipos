<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\InventoryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleStockDeductionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure User #1 exists for the SaleController
        User::factory()->create(['id' => 1]);
    }

    public function test_stock_deduction_works_correctly()
    {
        $product = Product::create([
            'barcode'   => '1111111111111',
            'name'      => 'Test Product',
            'price'     => 5.00,
            'stock_qty' => 20,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
            'payment_method' => 'cash',
            'currency'       => 'MYR',
        ]);

        $response->assertStatus(201);
        $this->assertEquals(18, $product->fresh()->stock_qty);
    }

    public function test_inventory_log_is_created()
    {
        $product = Product::create([
            'barcode'   => '2222222222222',
            'name'      => 'Log Product',
            'price'     => 3.00,
            'stock_qty' => 10,
            'is_active' => true,
        ]);

        $this->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
            'payment_method' => 'cash',
            'currency'       => 'MYR',
        ]);

        $this->assertDatabaseHas('inventory_logs', [
            'product_id' => $product->id,
            'change_qty' => -3,
            'reason'     => 'sale',
        ]);
    }

    public function test_sale_fails_if_stock_is_low()
    {
        $product = Product::create([
            'barcode'   => '3333333333333',
            'name'      => 'Low Stock',
            'price'     => 8.00,
            'stock_qty' => 1,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 5]],
            'payment_method' => 'cash',
            'currency'       => 'MYR',
        ]);

        $response->assertStatus(422); // Insufficient stock
    }

    public function test_invalid_barcode_format_is_rejected()
    {
        $response = $this->getJson('/api/products/barcode/!!!###');
        $response->assertStatus(422);
    }
}