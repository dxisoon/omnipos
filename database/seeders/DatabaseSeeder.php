<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Admin User with ID 1
        User::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Admin Cashier',
                'email' => 'admin@omnipos.com',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Create the Teh Tarik Product
        Product::updateOrCreate(
            ['barcode' => '1234567890123'],
            [
                'name' => 'Teh Tarik',
                'price' => 3.50,
                'stock_qty' => 100,
                'low_stock_threshold' => 10,
                'is_active' => true,
            ]
        );
    }
}