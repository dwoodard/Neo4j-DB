<?php

namespace Database\Seeders;

use Database\Factories\Neo4jPersonFactory;
use Database\Factories\Neo4jRelationshipFactory;
use Illuminate\Database\Seeder;

class Neo4jSeeder extends Seeder
{
    /**
     * Seed the Neo4j database with sample data.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding Neo4j database with sample data...');

        // Create persons
        $this->command->info('👤 Creating persons...');
        
        $personFactory = new Neo4jPersonFactory();
        $relationshipFactory = new Neo4jRelationshipFactory();

        // Create a diverse set of people
        $persons = [];

        // Tech team
        $this->command->info('   Creating tech team...');
        $persons[] = $personFactory->techWorker()->withName('Alice Chen')->withEmail('alice.chen@techcorp.com')->create();
        $persons[] = $personFactory->techWorker()->withName('Bob Johnson')->withEmail('bob.johnson@techcorp.com')->create();
        $persons[] = $personFactory->techWorker()->withName('Carol Smith')->withEmail('carol.smith@techcorp.com')->create();
        $persons[] = $personFactory->techWorker()->withName('David Rodriguez')->withEmail('david.r@techcorp.com')->create();

        // Business team
        $this->command->info('   Creating business team...');
        $persons[] = $personFactory->businessPro()->withName('Emma Wilson')->withEmail('emma.wilson@bizpro.com')->create();
        $persons[] = $personFactory->businessPro()->withName('Frank Miller')->withEmail('frank.miller@bizpro.com')->create();
        $persons[] = $personFactory->businessPro()->withName('Grace Lee')->withEmail('grace.lee@bizpro.com')->create();

        // Different age groups
        $this->command->info('   Creating people of different ages...');
        $persons[] = $personFactory->young()->withName('Henry Park')->create();
        $persons[] = $personFactory->middleAged()->withName('Isabel Garcia')->create();
        $persons[] = $personFactory->senior()->withName('Jack Thompson')->create();

        // Random people
        $this->command->info('   Creating additional random people...');
        for ($i = 0; $i < 5; $i++) {
            $persons[] = $personFactory->create();
        }

        $this->command->info(sprintf('   ✅ Created %d persons', count($persons)));

        // Create relationships
        $this->command->info('🔗 Creating relationships...');

        $relationships = [];

        // Tech team relationships
        $relationships[] = $relationshipFactory->between($persons[0]['id'], $persons[1]['id'])->workRelationship()->create();
        $relationships[] = $relationshipFactory->between($persons[1]['id'], $persons[2]['id'])->friendship()->create();
        $relationships[] = $relationshipFactory->between($persons[2]['id'], $persons[3]['id'])->mentorship()->create();

        // Business team relationships
        $relationships[] = $relationshipFactory->between($persons[4]['id'], $persons[5]['id'])->management()->create();
        $relationships[] = $relationshipFactory->between($persons[5]['id'], $persons[6]['id'])->workRelationship()->create();

        // Cross-team relationships
        $relationships[] = $relationshipFactory->between($persons[0]['id'], $persons[4]['id'])->workRelationship()->create();
        $relationships[] = $relationshipFactory->between($persons[2]['id'], $persons[6]['id'])->friendship()->create();

        // Family relationships
        $relationships[] = $relationshipFactory->between($persons[7]['id'], $persons[8]['id'])->family()->create();
        $relationships[] = $relationshipFactory->between($persons[8]['id'], $persons[9]['id'])->marriage()->create();

        // Academic relationships
        $relationships[] = $relationshipFactory->between($persons[1]['id'], $persons[7]['id'])->academic()->create();
        $relationships[] = $relationshipFactory->between($persons[3]['id'], $persons[8]['id'])->academic()->create();

        // Random relationships for remaining people
        $this->command->info('   Creating random relationships...');
        for ($i = 0; $i < 8; $i++) {
            try {
                $relationships[] = $relationshipFactory->createBetweenRandomPersons();
            } catch (\Exception $e) {
                $this->command->warn("   Skipped random relationship: {$e->getMessage()}");
            }
        }

        $this->command->info(sprintf('   ✅ Created %d relationships', count($relationships)));

        // Create some specific relationship patterns
        $this->command->info('🎯 Creating specific relationship patterns...');

        // Create a small network cluster
        if (count($persons) >= 4) {
            $clusterPersons = array_slice($persons, 10, 4);
            foreach ($clusterPersons as $i => $person1) {
                foreach ($clusterPersons as $j => $person2) {
                    if ($i < $j) { // Avoid duplicates and self-relationships
                        try {
                            $relationshipFactory->between($person1['id'], $person2['id'])
                                ->friendship()
                                ->strong()
                                ->create();
                        } catch (\Exception $e) {
                            // Relationship might already exist, skip
                        }
                    }
                }
            }
            $this->command->info('   ✅ Created friendship cluster');
        }

        $this->command->info('🎉 Neo4j seeding completed!');
        $this->command->info(sprintf('📊 Summary: %d persons, %d+ relationships', count($persons), count($relationships)));
    }
}
