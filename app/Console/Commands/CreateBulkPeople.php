<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Services\Neo4jService;
use Illuminate\Console\Command;

class CreateBulkPeople extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neo4j:create-people 
                            {--count=500 : Number of people to create}
                            {--batch-size=50 : Number of people to create per batch}
                            {--with-relationships : Create random relationships between people}
                            {--cleanup : Clean up existing bulk people before creating new ones}';

    /**
     * The console command description.
     */
    protected $description = 'Create bulk people for testing Neo4j Graph Model performance';

    private Neo4jService $neo4j;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->neo4j = app(Neo4jService::class);
        $count = (int) $this->option('count');
        $batchSize = (int) $this->option('batch-size');
        $withRelationships = $this->option('with-relationships');
        $cleanup = $this->option('cleanup');

        $this->info("🚀 Creating {$count} people in batches of {$batchSize}...");
        $this->newLine();

        // Cleanup existing bulk people if requested
        if ($cleanup) {
            $this->cleanupExistingBulkPeople();
        }

        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);
        $people = [];

        // Create people in batches
        $created = 0;
        while ($created < $count) {
            $batchEnd = min($created + $batchSize, $count);
            $batchCount = $batchEnd - $created;

            $this->info('📦 Creating batch '.(intval($created / $batchSize) + 1)." ({$batchCount} people)...");

            $batchPeople = $this->createPeopleBatch($created + 1, $batchEnd);
            $people = array_merge($people, $batchPeople);
            $created = $batchEnd;

            $elapsed = microtime(true) - $startTime;
            $rate = $created / $elapsed;
            $this->line("  ✅ Created {$created}/{$count} people (".round($rate, 1).' people/sec)');
        }

        $creationTime = microtime(true) - $startTime;
        $this->info("✅ Created {$count} people in ".round($creationTime, 2).' seconds');
        $this->line('📊 Average creation rate: '.round($count / $creationTime, 1).' people/second');

        // Create relationships if requested
        if ($withRelationships && count($people) > 1) {
            $this->createRandomRelationships($people);
        }

        // Performance summary
        $memoryEnd = memory_get_usage(true);
        $memoryUsed = ($memoryEnd - $memoryStart) / 1024 / 1024;
        $totalTime = microtime(true) - $startTime;

        $this->newLine();
        $this->info('📊 Performance Summary:');
        $this->line('  - Total time: '.round($totalTime, 2).' seconds');
        $this->line('  - Memory used: '.round($memoryUsed, 2).' MB');
        $this->line("  - People created: {$count}");
        if ($withRelationships) {
            $this->line('  - Relationships created: ~'.intval($count * 0.3).' (estimated)');
        }

        // Test query performance with the new dataset
        $this->testQueryPerformance();

        return 0;
    }

    /**
     * Create a batch of people
     */
    protected function createPeopleBatch(int $start, int $end): array
    {
        $people = [];
        $companies = ['TechCorp', 'DataSoft', 'CloudSys', 'InnovateInc', 'DevStudio', 'WebWorks', 'AppCo', 'SoftLab'];
        $occupations = ['Developer', 'Designer', 'Manager', 'Analyst', 'Engineer', 'Architect', 'Consultant', 'Specialist'];
        $departments = ['Engineering', 'Marketing', 'Sales', 'HR', 'Finance', 'Operations', 'R&D', 'Support'];

        for ($i = $start; $i <= $end; $i++) {
            $person = Person::create([
                'name' => "Bulk Person {$i}",
                'email' => "person{$i}@bulk-test.com",
                'age' => rand(22, 65),
                'occupation' => $occupations[array_rand($occupations)],
                'company' => $companies[array_rand($companies)],
                'department' => $departments[array_rand($departments)],
                'salary' => rand(40000, 150000),
                'hire_date' => date('Y-m-d', strtotime('-'.rand(1, 2000).' days')),
                'is_active' => rand(0, 10) > 1, // 90% active
                'skills' => $this->generateRandomSkills(),
                'location' => $this->generateRandomLocation(),
            ]);

            $people[] = $person;
        }

        return $people;
    }

    /**
     * Generate random skills for a person
     */
    protected function generateRandomSkills(): string
    {
        $allSkills = [
            'PHP', 'JavaScript', 'Python', 'Java', 'C#', 'Go', 'Rust', 'TypeScript',
            'React', 'Vue', 'Angular', 'Laravel', 'Django', 'Spring', 'Node.js',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Neo4j', 'Elasticsearch',
            'Docker', 'Kubernetes', 'AWS', 'Azure', 'GCP', 'CI/CD', 'Git',
            'Project Management', 'Team Leadership', 'Agile', 'Scrum', 'DevOps',
        ];

        $numSkills = rand(3, 8);
        $selectedSkills = array_rand($allSkills, $numSkills);

        if (is_array($selectedSkills)) {
            return implode(', ', array_map(function ($i) use ($allSkills) {
                return $allSkills[$i];
            }, $selectedSkills));
        } else {
            return $allSkills[$selectedSkills];
        }
    }

    /**
     * Generate random location
     */
    protected function generateRandomLocation(): string
    {
        $locations = [
            'New York, NY', 'San Francisco, CA', 'Seattle, WA', 'Austin, TX', 'Chicago, IL',
            'Boston, MA', 'Denver, CO', 'Atlanta, GA', 'Los Angeles, CA', 'Miami, FL',
            'Toronto, ON', 'Vancouver, BC', 'London, UK', 'Berlin, Germany', 'Amsterdam, NL',
            'Remote', 'Paris, France', 'Sydney, Australia', 'Tokyo, Japan', 'Singapore',
        ];

        return $locations[array_rand($locations)];
    }

    /**
     * Create random relationships between people
     */
    protected function createRandomRelationships(array $people): void
    {
        $this->info('🔗 Creating random relationships...');
        $relationshipStart = microtime(true);
        $relationshipCount = 0;

        // Create relationships for each person (30% chance with each other person)
        $totalPeople = count($people);
        $progressBar = $this->output->createProgressBar($totalPeople);

        foreach ($people as $i => $person) {
            $relationshipsForPerson = 0;
            $maxRelationships = rand(2, 8); // Each person gets 2-8 relationships

            for ($j = 0; $j < $totalPeople && $relationshipsForPerson < $maxRelationships; $j++) {
                if ($i !== $j && rand(1, 100) <= 30) { // 30% chance
                    $target = $people[$j];
                    $relationshipType = rand(1, 3);

                    try {
                        switch ($relationshipType) {
                            case 1:
                                $person->addFriend($target);
                                break;
                            case 2:
                                $person->addColleague($target);
                                break;
                            case 3:
                                if (rand(1, 10) === 1) { // 10% chance for manager relationship
                                    $person->manage($target);
                                }
                                break;
                        }
                        $relationshipCount++;
                        $relationshipsForPerson++;
                    } catch (\Exception $e) {
                        // Skip if relationship already exists or other error
                    }
                }
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $relationshipTime = microtime(true) - $relationshipStart;
        $this->info("✅ Created {$relationshipCount} relationships in ".round($relationshipTime, 2).' seconds');
        $this->line('📊 Average relationship creation rate: '.round($relationshipCount / $relationshipTime, 1).' relationships/second');
    }

    /**
     * Test query performance with the new dataset
     */
    protected function testQueryPerformance(): void
    {
        $this->info('🔍 Testing query performance...');
        $this->newLine();

        // Test 1: Count all people
        $start = microtime(true);
        $totalCount = Person::count();
        $countTime = microtime(true) - $start;
        $this->line("📊 Total people count: {$totalCount} (took ".round($countTime * 1000, 2).'ms)');

        // Test 2: Query by company
        $start = microtime(true);
        $techCorpPeople = Person::where('company', '=', 'TechCorp')->get();
        $companyQueryTime = microtime(true) - $start;
        $this->line('📊 TechCorp employees: '.$techCorpPeople->count().' (took '.round($companyQueryTime * 1000, 2).'ms)');

        // Test 3: Complex query (age range + occupation)
        $start = microtime(true);
        $developers = Person::where('occupation', '=', 'Developer')
            ->where('age', '>=', 25)
            ->where('age', '<=', 40)
            ->get();
        $complexQueryTime = microtime(true) - $start;
        $this->line('📊 Young developers (25-40): '.$developers->count().' (took '.round($complexQueryTime * 1000, 2).'ms)');

        // Test 4: Search query
        $start = microtime(true);
        $searchResults = Person::search('Person 1')->get();
        $searchTime = microtime(true) - $start;
        $this->line("📊 Search 'Person 1': ".$searchResults->count().' results (took '.round($searchTime * 1000, 2).'ms)');

        // Test 5: Relationship query (if relationships exist)
        if ($this->option('with-relationships')) {
            $start = microtime(true);
            $randomPerson = Person::where('name', 'CONTAINS', 'Person 1')->first();
            if ($randomPerson) {
                $friends = $randomPerson->getFriends();
                $relationshipQueryTime = microtime(true) - $start;
                $this->line("📊 Friends of {$randomPerson->name}: ".$friends->count().' (took '.round($relationshipQueryTime * 1000, 2).'ms)');
            }
        }
    }

    /**
     * Clean up existing bulk people
     */
    protected function cleanupExistingBulkPeople(): void
    {
        $this->info('🧹 Cleaning up existing bulk test data...');

        $query = "MATCH (p:Person) WHERE p.email ENDS WITH '@bulk-test.com' DETACH DELETE p";
        $this->neo4j->runQuery($query);

        $this->line('✅ Cleaned up existing bulk people');
        $this->newLine();
    }
}
