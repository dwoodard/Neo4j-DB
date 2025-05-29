<?php

namespace App\Console\Commands;

use App\Models\Person;
use Illuminate\Console\Command;

class TestGraphModel extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neo4j:test-graph-model 
                            {--action=all : Action to perform (all, create, query, relationships, cleanup)}
                            {--count=10 : Number of people to create for testing}';

    /**
     * The console command description.
     */
    protected $description = 'Test the Neo4j Graph Model system with various operations';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->option('action');

        switch ($action) {
            case 'all':
                $this->runAllTests();
                break;
            case 'create':
                $this->testCreate();
                break;
            case 'query':
                $this->testQueries();
                break;
            case 'relationships':
                $this->testRelationships();
                break;
            case 'cleanup':
                $this->cleanup();
                break;
            default:
                $this->error("Unknown action: $action");

                return 1;
        }

        return 0;
    }

    /**
     * Run all tests
     */
    protected function runAllTests(): void
    {
        $this->info('🧪 Running comprehensive Graph Model tests...');

        $this->testCreate();
        $this->testQueries();
        $this->testRelationships();
        $this->testAdvancedQueries();

        $this->info('✅ All tests completed!');
    }

    /**
     * Test creating people
     */
    protected function testCreate(): void
    {
        $this->info('🔨 Testing Person creation...');

        $count = (int) $this->option('count');
        $created = 0;

        for ($i = 1; $i <= $count; $i++) {
            try {
                $person = Person::create([
                    'name' => "Test Person $i",
                    'email' => "test$i@example.com",
                    'age' => rand(18, 65),
                    'gender' => ['Male', 'Female', 'Other'][rand(0, 2)],
                    'occupation' => ['Developer', 'Designer', 'Manager', 'Analyst'][rand(0, 3)],
                    'company' => ['TechCorp', 'DesignStudio', 'DataInc'][rand(0, 2)],
                    'department' => ['Engineering', 'Design', 'Sales', 'Marketing'][rand(0, 3)],
                    'salary' => rand(40000, 120000),
                    'skills' => json_encode(['PHP', 'JavaScript', 'Python'][rand(0, 2)] ?
                               ['PHP', 'JavaScript'] : ['Python', 'React']),
                    'active' => true,
                ]);

                $created++;
                $this->line("Created: {$person->name} (ID: {$person->getId()})");

            } catch (\Exception $e) {
                $this->error("Failed to create person $i: ".$e->getMessage());
            }
        }

        $this->info("✅ Created $created people");
    }

    /**
     * Test basic queries
     */
    protected function testQueries(): void
    {
        $this->info('🔍 Testing queries...');

        // Test Person::all()
        $allPeople = Person::all();
        $this->line('Total people: '.$allPeople->count());

        if ($allPeople->count() > 0) {
            // Test Person::find()
            $firstPerson = $allPeople->first();
            $found = Person::find($firstPerson->getId());
            $this->line('Found person by ID: '.($found ? $found->name : 'Not found'));

            // Test where queries
            $adults = Person::where('age', '>', 30)->get();
            $this->line('Adults (age > 30): '.$adults->count());

            $developers = Person::where('occupation', '=', 'Developer')->get();
            $this->line('Developers: '.$developers->count());

            // Test multiple where clauses
            $youngDevelopers = Person::where('age', '<', 35)
                ->where('occupation', '=', 'Developer')
                ->get();
            $this->line('Young developers: '.$youngDevelopers->count());

            // Test ordering
            $orderedByAge = Person::where('age', '>', 0)
                ->orderBy('age', 'DESC')
                ->limit(5)
                ->get();
            $this->line('Top 5 oldest people:');
            foreach ($orderedByAge as $person) {
                $this->line("  - {$person->name} (age: {$person->age})");
            }

            // Test search
            $searchResults = Person::search('Test')->limit(3)->get();
            $this->line("Search results for 'Test': ".$searchResults->count());

            // Test scopes
            $activeAdults = Person::adults()->active()->get();
            $this->line('Active adults: '.$activeAdults->count());

            $techCorpEmployees = Person::inCompany('TechCorp')->get();
            $this->line('TechCorp employees: '.$techCorpEmployees->count());
        }

        $this->info('✅ Query tests completed');
    }

    /**
     * Test relationships
     */
    protected function testRelationships(): void
    {
        $this->info('🔗 Testing relationships...');

        $people = Person::limit(5)->get();

        if ($people->count() >= 2) {
            $person1 = $people[0];
            $person2 = $people[1];

            // Debug: Show IDs
            $this->line("Person1 ID: {$person1->getId()}, Person2 ID: {$person2->getId()}");

            // Test friend relationship
            $result = $person1->addFriend($person2);
            $this->line($result ?
                "✅ Created friendship: {$person1->name} -> {$person2->name}" :
                '❌ Failed to create friendship'
            );

            // Test colleague relationship
            if (count($people) >= 3) {
                $person3 = $people[2];
                $this->line("Person3 ID: {$person3->getId()}");
                $result = $person1->addColleague($person3, ['since' => '2023-01-01']);
                $this->line($result ?
                    "✅ Created colleague relationship: {$person1->name} -> {$person3->name}" :
                    '❌ Failed to create colleague relationship'
                );
            }

            // Test management relationship
            if (count($people) >= 4) {
                $manager = $people[3];
                $this->line("Manager ID: {$manager->getId()}");
                $result = $manager->manage($person1);
                $this->line($result ?
                    "✅ Created management relationship: {$manager->name} manages {$person1->name}" :
                    '❌ Failed to create management relationship'
                );
            }

            // Test retrieving relationships
            $friends = $person1->getFriends();
            $this->line("{$person1->name} has {$friends->count()} friend(s)");

            $manager = $person1->getManager();
            $this->line("{$person1->name}'s manager: ".($manager ? $manager->name : 'None'));
        }

        $this->info('✅ Relationship tests completed');
    }

    /**
     * Test advanced queries
     */
    protected function testAdvancedQueries(): void
    {
        $this->info('🎯 Testing advanced queries...');

        // Test pagination
        $page1 = Person::where('age', '>', 0)->paginate(3, 1);
        $this->line("Page 1 results: {$page1['data']->count()}/{$page1['total']} total");

        // Test exists
        $hasAdults = Person::adults()->exists();
        $this->line('Has adults: '.($hasAdults ? 'Yes' : 'No'));

        // Test count
        $totalCount = Person::count();
        $adultCount = Person::adults()->count();
        $this->line("Total people: $totalCount, Adults: $adultCount");

        // Test whereIn
        $companies = ['TechCorp', 'DesignStudio'];
        $companyEmployees = Person::whereIn('company', $companies)->get();
        $this->line('Employees in specific companies: '.$companyEmployees->count());

        // Test age range
        $millennials = Person::ageRange(25, 40)->get();
        $this->line('People aged 25-40: '.$millennials->count());

        // Test model methods
        if ($totalCount > 0) {
            $person = Person::first();
            $summary = $person->getSummary();
            $this->line('Sample person summary:');
            foreach ($summary as $key => $value) {
                $this->line("  $key: $value");
            }
        }

        $this->info('✅ Advanced query tests completed');
    }

    /**
     * Clean up test data
     */
    protected function cleanup(): void
    {
        $this->info('🧹 Cleaning up test data...');

        if ($this->confirm('Are you sure you want to delete all test people?')) {
            $testPeople = Person::where('name', 'STARTS WITH', 'Test Person')->get();
            $deleted = 0;

            foreach ($testPeople as $person) {
                if ($person->delete()) {
                    $deleted++;
                }
            }

            $this->info("✅ Deleted $deleted test people");
        } else {
            $this->info('Cleanup cancelled');
        }
    }
}
