<?php

namespace App\Console\Commands;

use App\Services\Neo4jAnalyticsService;
use App\Services\Neo4jBatchService;
use App\Services\Neo4jFactoryService;
use Illuminate\Console\Command;

class Neo4jAnalytics extends Command
{
    protected $signature = 'neo4j:analytics 
                           {action : The action to perform (report|performance|optimize|benchmark)}
                           {--format=table : Output format (table|json|csv)}
                           {--save : Save report to file}';

    protected $description = 'Generate analytics reports and performance tests for Neo4j data';

    public function handle(
        Neo4jAnalyticsService $analytics,
        Neo4jBatchService $batch,
        Neo4jFactoryService $factory
    ) {
        $action = $this->argument('action');

        switch ($action) {
            case 'report':
                return $this->generateReport($analytics);
            
            case 'performance':
                return $this->runPerformanceTest($batch, $factory);
            
            case 'optimize':
                return $this->optimizeDatabase($batch);
            
            case 'benchmark':
                return $this->runBenchmark($batch, $factory);
            
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Available actions: report, performance, optimize, benchmark');
                return 1;
        }
    }

    /**
     * Generate comprehensive analytics report
     */
    protected function generateReport(Neo4jAnalyticsService $analytics): int
    {
        $this->info('📊 Generating Neo4j Analytics Report...');
        $this->line('');

        try {
            $report = $analytics->generateReport();
            $format = $this->option('format');

            switch ($format) {
                case 'json':
                    $this->displayJsonReport($report);
                    break;
                
                case 'csv':
                    $this->displayCsvReport($report);
                    break;
                
                default:
                    $this->displayTableReport($report);
                    break;
            }

            if ($this->option('save')) {
                $this->saveReport($report, $format);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("Failed to generate report: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Display report in table format
     */
    protected function displayTableReport(array $report): void
    {
        // Summary
        $this->info('📈 Network Summary');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Nodes', $report['summary']['total_nodes']],
                ['Total Relationships', $report['summary']['total_relationships']],
                ['Network Density', round($report['summary']['network_density'], 4)],
                ['Most Connected Person', $report['summary']['most_connected']['name'] ?? 'N/A'],
            ]
        );
        $this->line('');

        // Node counts
        $this->info('🎯 Node Types');
        $nodeData = [];
        foreach ($report['detailed_statistics']['nodes'] as $type => $count) {
            $nodeData[] = [$type, $count];
        }
        $this->table(['Node Type', 'Count'], $nodeData);
        $this->line('');

        // Relationship counts
        $this->info('🔗 Relationship Types');
        $relData = [];
        foreach ($report['detailed_statistics']['relationships'] as $type => $count) {
            $relData[] = [$type, $count];
        }
        $this->table(['Relationship Type', 'Count'], $relData);
        $this->line('');

        // Demographics
        $this->info('👥 Age Distribution');
        $ageData = [];
        foreach ($report['detailed_statistics']['demographics']['age_distribution'] as $age => $count) {
            $ageData[] = [$age, $count];
        }
        $this->table(['Age Group', 'Count'], $ageData);
        $this->line('');

        // Top occupations
        $this->info('💼 Top Occupations');
        $occData = [];
        $occupations = array_slice($report['detailed_statistics']['demographics']['occupation_distribution'], 0, 10, true);
        foreach ($occupations as $occ => $count) {
            $occData[] = [$occ, $count];
        }
        $this->table(['Occupation', 'Count'], $occData);
        $this->line('');

        // Most connected nodes
        $this->info('🌟 Most Connected People');
        $connectedData = [];
        foreach (array_slice($report['detailed_statistics']['network_metrics']['most_connected_nodes'], 0, 10) as $node) {
            $connectedData[] = [$node['name'], $node['occupation'], $node['degree']];
        }
        $this->table(['Name', 'Occupation', 'Connections'], $connectedData);
        $this->line('');

        // Insights
        $this->info('💡 Key Insights');
        foreach ($report['insights'] as $insight) {
            $this->line("   • {$insight}");
        }
    }

    /**
     * Display report in JSON format
     */
    protected function displayJsonReport(array $report): void
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT));
    }

    /**
     * Display report in CSV format
     */
    protected function displayCsvReport(array $report): void
    {
        $this->info('CSV format - Summary data:');
        
        // Basic CSV output
        $csvData = [
            ['Metric', 'Value'],
            ['Total Nodes', $report['summary']['total_nodes']],
            ['Total Relationships', $report['summary']['total_relationships']],
            ['Network Density', $report['summary']['network_density']],
        ];

        foreach ($csvData as $row) {
            $this->line(implode(',', $row));
        }
    }

    /**
     * Save report to file
     */
    protected function saveReport(array $report, string $format): void
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "neo4j_analytics_report_{$timestamp}";
        
        switch ($format) {
            case 'json':
                $filepath = storage_path("app/reports/{$filename}.json");
                file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));
                break;
            
            case 'csv':
                $filepath = storage_path("app/reports/{$filename}.csv");
                // Simplified CSV export
                $csv = "Metric,Value\n";
                $csv .= "Total Nodes,{$report['summary']['total_nodes']}\n";
                $csv .= "Total Relationships,{$report['summary']['total_relationships']}\n";
                $csv .= "Network Density,{$report['summary']['network_density']}\n";
                file_put_contents($filepath, $csv);
                break;
            
            default:
                $filepath = storage_path("app/reports/{$filename}.txt");
                file_put_contents($filepath, print_r($report, true));
                break;
        }

        // Ensure directory exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($filepath, $format === 'json' ? json_encode($report, JSON_PRETTY_PRINT) : print_r($report, true));
        $this->info("Report saved to: {$filepath}");
    }

    /**
     * Run performance tests
     */
    protected function runPerformanceTest(Neo4jBatchService $batch, Neo4jFactoryService $factory): int
    {
        $this->info('🚀 Running Performance Tests...');
        $this->line('');

        $tests = [
            '100 nodes batch creation',
            '500 nodes batch creation', 
            '1000 nodes batch creation',
            '100 relationships batch creation',
            '500 relationships batch creation',
        ];

        foreach ($tests as $test) {
            $this->info("Testing: {$test}");
            
            $startTime = microtime(true);
            
            try {
                switch ($test) {
                    case '100 nodes batch creation':
                        $this->createTestNodes($batch, 100);
                        break;
                    case '500 nodes batch creation':
                        $this->createTestNodes($batch, 500);
                        break;
                    case '1000 nodes batch creation':
                        $this->createTestNodes($batch, 1000);
                        break;
                    case '100 relationships batch creation':
                        $this->createTestRelationships($batch, 100);
                        break;
                    case '500 relationships batch creation':
                        $this->createTestRelationships($batch, 500);
                        break;
                }
                
                $duration = round((microtime(true) - $startTime) * 1000, 2);
                $this->line("   ✅ Completed in {$duration}ms");
                
            } catch (\Exception $e) {
                $this->error("   ❌ Failed: {$e->getMessage()}");
            }
            
            $this->line('');
        }

        return 0;
    }

    /**
     * Create test nodes for performance testing
     */
    protected function createTestNodes(Neo4jBatchService $batch, int $count): void
    {
        $faker = \Faker\Factory::create();
        $nodes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $nodes[] = [
                'name' => $faker->name(),
                'email' => $faker->email(),
                'age' => $faker->numberBetween(18, 80),
                'phone' => $faker->phoneNumber(),
                'address' => $faker->address(),
                'occupation' => $faker->jobTitle(),
                'company' => $faker->company(),
                'bio' => $faker->sentence(),
            ];
        }
        
        $batch->createPersonsBatch($nodes);
        
        // Clean up test data
        if ($count >= 500) {
            $batch->bulkDelete('TestPerson');
        }
    }

    /**
     * Create test relationships for performance testing
     */
    protected function createTestRelationships(Neo4jBatchService $batch, int $count): void
    {
        // This would need existing nodes to work properly
        // For now, just measure the time for a simpler operation
        $this->line("   (Skipped - requires existing nodes)");
    }

    /**
     * Optimize database performance
     */
    protected function optimizeDatabase(Neo4jBatchService $batch): int
    {
        $this->info('⚡ Optimizing Neo4j Database...');
        $this->line('');

        try {
            $this->info('Creating indexes...');
            $batch->createIndexes();
            $this->line('   ✅ Indexes created successfully');
            
            $this->line('');
            $this->info('🎯 Optimization Complete!');
            $this->info('Recommendations:');
            $this->line('   • Indexes have been created for common queries');
            $this->line('   • Monitor query performance with PROFILE');
            $this->line('   • Consider adding more specific indexes for your use case');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("Optimization failed: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Run comprehensive benchmark
     */
    protected function runBenchmark(Neo4jBatchService $batch, Neo4jFactoryService $factory): int
    {
        $this->info('🏁 Running Neo4j Benchmark Suite...');
        $this->line('');

        // Individual vs batch creation comparison
        $this->info('📊 Comparing Individual vs Batch Creation');
        
        // Individual creation
        $this->info('Testing individual node creation (10 nodes)...');
        $startTime = microtime(true);
        for ($i = 0; $i < 10; $i++) {
            $factory->person()->create();
        }
        $individualTime = (microtime(true) - $startTime) * 1000;
        $this->line("   Individual: {$individualTime}ms");

        // Batch creation
        $this->info('Testing batch node creation (10 nodes)...');
        $this->createTestNodes($batch, 10);
        $batchTime = (microtime(true) - $startTime) * 1000;
        $this->line("   Batch: {$batchTime}ms");

        if ($batchTime < $individualTime) {
            $improvement = round((($individualTime - $batchTime) / $individualTime) * 100, 1);
            $this->info("   🚀 Batch creation is {$improvement}% faster!");
        }

        $this->line('');
        $this->info('✨ Benchmark Complete!');

        return 0;
    }
}
