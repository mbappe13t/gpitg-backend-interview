<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
   
    public function run(): void
    {
        Product::query()->insert([
            [
                'name' => 'Wireless Headphones',
                'description' => 'Comfortable wireless headphones with clear sound.',
                'price' => 79.99,
            ],
            [
                'name' => 'Mechanical Keyboard',
                'description' => 'A durable mechanical keyboard for work and gaming.',
                'price' => 109.50,
            ],
            [
                'name' => 'USB-C Hub',
                'description' => 'A compact hub with USB, HDMI, and card reader ports.',
                'price' => 44.95,
            ],
        ]);
    }
}
