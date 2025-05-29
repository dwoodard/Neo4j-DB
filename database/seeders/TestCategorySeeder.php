<?php

namespace Database\Seeders;

use App\Models\TestCategory;
use Illuminate\Database\Seeder;

class TestCategorySeeder extends Seeder
{
    /**
     * Seed the database with TestCategory data.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding TestCategory data...');

        // Create sample testcategories
        $sampleTestCategorys = [
            [
                'name' => 'Sample TestCategory 1',
                // Add other required attributes
            ],
            [
                'name' => 'Sample TestCategory 2',
                // Add other required attributes
            ],
            [
                'name' => 'Sample TestCategory 3',
                // Add other required attributes
            ],
        ];

        foreach ($sampleTestCategorys as $data) {
            TestCategory::create($data);
            $this->command->info("   Created testcategory: {$data['name']}");
        }

        $this->command->info("✅ TestCategory seeding completed!");
    }
}