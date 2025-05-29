<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Person;
use App\Services\Neo4jService;
use Illuminate\Console\Command;

class DatabaseStats extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neo4j:stats 
                            {--detailed : Show detailed statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Show comprehensive Neo4j database statistics';

    private Neo4jService $neo4j;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->neo4j = app(Neo4jService::class);
        $detailed = $this->option('detailed');

        $this->info('📊 Neo4j Database Statistics');
        $this->line('='.str_repeat('=', 40));
        $this->newLine();

        // Basic node counts
        $this->showBasicStats();
        $this->newLine();

        // People statistics
        $this->showPeopleStats();
        $this->newLine();

        // Company statistics
        $this->showCompanyStats();
        $this->newLine();

        // Relationship statistics
        $this->showRelationshipStats();
        $this->newLine();

        // Performance metrics
        $this->showPerformanceMetrics();
        $this->newLine();

        if ($detailed) {
            $this->showDetailedStats();
        }

        return 0;
    }

    /**
     * Show basic database statistics
     */
    protected function showBasicStats(): void
    {
        $this->info('🔢 Basic Statistics:');

        try {
            // Total nodes
            $nodeQuery = 'MATCH (n) RETURN count(n) as total';
            $totalNodes = $this->neo4j->runQuery($nodeQuery)->first()->get('total');
            $this->line("  Total Nodes: {$totalNodes}");

            // Total relationships
            $relQuery = 'MATCH ()-[r]->() RETURN count(r) as total';
            $totalRels = $this->neo4j->runQuery($relQuery)->first()->get('total');
            $this->line("  Total Relationships: {$totalRels}");

            // Node labels
            $labelQuery = 'CALL db.labels()';
            $labels = collect($this->neo4j->runQuery($labelQuery))
                ->map(fn ($record) => $record->get('label'))
                ->toArray();
            $this->line('  Node Labels: '.implode(', ', $labels));

            // Relationship types
            $typeQuery = 'CALL db.relationshipTypes()';
            $types = collect($this->neo4j->runQuery($typeQuery))
                ->map(fn ($record) => $record->get('relationshipType'))
                ->toArray();
            $this->line('  Relationship Types: '.implode(', ', $types));

        } catch (\Exception $e) {
            $this->error('  Error getting basic stats: '.$e->getMessage());
        }
    }

    /**
     * Show people-specific statistics
     */
    protected function showPeopleStats(): void
    {
        $this->info('👥 People Statistics:');

        try {
            $totalPeople = Person::count();
            $this->line("  Total People: {$totalPeople}");

            if ($totalPeople > 0) {
                // Age distribution
                $ageQuery = '
                    MATCH (p:Person) 
                    WHERE p.age IS NOT NULL 
                    RETURN 
                        min(p.age) as min_age,
                        max(p.age) as max_age,
                        avg(p.age) as avg_age
                ';
                $ageStats = $this->neo4j->runQuery($ageQuery)->first();
                $this->line('  Age Range: '.$ageStats->get('min_age').' - '.$ageStats->get('max_age'));
                $this->line('  Average Age: '.round($ageStats->get('avg_age'), 1));

                // Company distribution
                $companyQuery = '
                    MATCH (p:Person) 
                    WHERE p.company IS NOT NULL 
                    RETURN p.company as company, count(p) as count 
                    ORDER BY count DESC 
                    LIMIT 5
                ';
                $topCompanies = collect($this->neo4j->runQuery($companyQuery));
                if ($topCompanies->isNotEmpty()) {
                    $this->line('  Top Companies:');
                    foreach ($topCompanies as $company) {
                        $this->line('    - '.$company->get('company').': '.$company->get('count').' people');
                    }
                }

                // Occupation distribution
                $occupationQuery = '
                    MATCH (p:Person) 
                    WHERE p.occupation IS NOT NULL 
                    RETURN p.occupation as occupation, count(p) as count 
                    ORDER BY count DESC 
                    LIMIT 5
                ';
                $topOccupations = collect($this->neo4j->runQuery($occupationQuery));
                if ($topOccupations->isNotEmpty()) {
                    $this->line('  Top Occupations:');
                    foreach ($topOccupations as $occupation) {
                        $this->line('    - '.$occupation->get('occupation').': '.$occupation->get('count').' people');
                    }
                }
            }

        } catch (\Exception $e) {
            $this->error('  Error getting people stats: '.$e->getMessage());
        }
    }

    /**
     * Show company statistics
     */
    protected function showCompanyStats(): void
    {
        $this->info('🏢 Company Statistics:');

        try {
            $totalCompanies = Company::count();
            $this->line("  Total Companies: {$totalCompanies}");

            if ($totalCompanies > 0) {
                // Industry distribution
                $industryQuery = '
                    MATCH (c:Company) 
                    WHERE c.industry IS NOT NULL 
                    RETURN c.industry as industry, count(c) as count 
                    ORDER BY count DESC
                ';
                $industries = collect($this->neo4j->runQuery($industryQuery));
                if ($industries->isNotEmpty()) {
                    $this->line('  Industries:');
                    foreach ($industries as $industry) {
                        $this->line('    - '.$industry->get('industry').': '.$industry->get('count').' companies');
                    }
                }

                // Size distribution
                $sizeQuery = '
                    MATCH (c:Company) 
                    WHERE c.size IS NOT NULL 
                    RETURN 
                        min(c.size) as min_size,
                        max(c.size) as max_size,
                        avg(c.size) as avg_size
                ';
                $sizeStats = $this->neo4j->runQuery($sizeQuery)->first();
                $this->line('  Size Range: '.$sizeStats->get('min_size').' - '.$sizeStats->get('max_size').' employees');
                $this->line('  Average Size: '.round($sizeStats->get('avg_size'), 1).' employees');
            }

        } catch (\Exception $e) {
            $this->error('  Error getting company stats: '.$e->getMessage());
        }
    }

    /**
     * Show relationship statistics
     */
    protected function showRelationshipStats(): void
    {
        $this->info('🔗 Relationship Statistics:');

        try {
            // Relationship type counts
            $relTypeQuery = '
                MATCH ()-[r]->() 
                RETURN type(r) as rel_type, count(r) as count 
                ORDER BY count DESC
            ';
            $relTypes = collect($this->neo4j->runQuery($relTypeQuery));

            if ($relTypes->isNotEmpty()) {
                $this->line('  Relationship Types:');
                foreach ($relTypes as $relType) {
                    $this->line('    - '.$relType->get('rel_type').': '.$relType->get('count').' relationships');
                }
            }

            // Most connected people
            $connectivityQuery = '
                MATCH (p:Person)-[r]->() 
                RETURN p.name as name, count(r) as connections 
                ORDER BY connections DESC 
                LIMIT 5
            ';
            $mostConnected = collect($this->neo4j->runQuery($connectivityQuery));

            if ($mostConnected->isNotEmpty()) {
                $this->line('  Most Connected People:');
                foreach ($mostConnected as $person) {
                    $this->line('    - '.$person->get('name').': '.$person->get('connections').' connections');
                }
            }

        } catch (\Exception $e) {
            $this->error('  Error getting relationship stats: '.$e->getMessage());
        }
    }

    /**
     * Show performance metrics
     */
    protected function showPerformanceMetrics(): void
    {
        $this->info('⚡ Performance Metrics:');

        try {
            // Test query performance
            $start = microtime(true);
            $peopleCount = Person::count();
            $countTime = microtime(true) - $start;
            $this->line('  Count Query: '.round($countTime * 1000, 2)."ms ({$peopleCount} people)");

            $start = microtime(true);
            $complexQuery = Person::where('age', '>=', 30)->where('occupation', '=', 'Developer')->get();
            $complexTime = microtime(true) - $start;
            $this->line('  Complex Query: '.round($complexTime * 1000, 2).'ms ('.$complexQuery->count().' results)');

            $start = microtime(true);
            $searchResults = Person::search('Person')->limit(10)->get();
            $searchTime = microtime(true) - $start;
            $this->line('  Search Query: '.round($searchTime * 1000, 2).'ms ('.$searchResults->count().' results)');

            // Test relationship query
            $randomPerson = Person::first();
            if ($randomPerson) {
                $start = microtime(true);
                $friends = $randomPerson->getFriends();
                $relTime = microtime(true) - $start;
                $this->line('  Relationship Query: '.round($relTime * 1000, 2).'ms ('.$friends->count().' friends)');
            }

        } catch (\Exception $e) {
            $this->error('  Error getting performance metrics: '.$e->getMessage());
        }
    }

    /**
     * Show detailed statistics
     */
    protected function showDetailedStats(): void
    {
        $this->info('🔍 Detailed Statistics:');

        try {
            // Memory usage
            $memoryQuery = "CALL dbms.queryJmx('java.lang:type=Memory') YIELD attributes RETURN attributes.HeapMemoryUsage.used as heap";
            $memory = $this->neo4j->runQuery($memoryQuery)->first();
            $heapMB = round($memory->get('heap') / 1024 / 1024, 2);
            $this->line("  Heap Memory Usage: {$heapMB} MB");

            // Database size
            $sizeQuery = 'CALL apoc.monitor.store() YIELD logSize, stringStoreSize, arrayStoreSize, relStoreSize, propStoreSize, totalStoreSize';
            $sizeResult = $this->neo4j->runQuery($sizeQuery)->first();
            $totalSizeMB = round($sizeResult->get('totalStoreSize') / 1024 / 1024, 2);
            $this->line("  Database Size: {$totalSizeMB} MB");

            // Index information
            $indexQuery = 'SHOW INDEXES';
            $indexes = collect($this->neo4j->runQuery($indexQuery));
            $this->line('  Active Indexes: '.$indexes->count());

            // Constraint information
            $constraintQuery = 'SHOW CONSTRAINTS';
            $constraints = collect($this->neo4j->runQuery($constraintQuery));
            $this->line('  Active Constraints: '.$constraints->count());

        } catch (\Exception $e) {
            $this->warn('  Some detailed stats unavailable: '.$e->getMessage());
        }
    }
}
