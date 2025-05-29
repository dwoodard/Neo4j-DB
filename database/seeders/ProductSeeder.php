<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the database with Product data.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Product data...');

        // Create sample products
        $sampleProducts = [
            [
                'name' => 'Sample Product 1',
                // Add other required attributes
            ],
            [
                'name' => 'Sample Product 2',
                // Add other required attributes
            ],
            [
                'name' => 'Sample Product 3',
                // Add other required attributes
            ],
        ];

        foreach ($sampleProducts as $data) {
            Product::create($data);
            $this->command->info("   Created product: {$data['name']}");
        }

        $this->command->info("✅ Product seeding completed!");
    }
}