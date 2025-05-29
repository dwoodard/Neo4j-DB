<?php

namespace App\Console\Commands;

use App\Services\Neo4jService;
use Illuminate\Console\Command;

class Neo4jMaintenance extends Command
{
    protected $signature = 'neo4j:maintenance {action : The maintenance action (stats|cleanup|reset)}';

    protected $description = 'Neo4j database maintenance operations';

    public function handle(Neo4jService $neo4j)
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'stats':
                $this->showStats($neo4j);
                break;
            case 'cleanup':
                $this->cleanup($neo4j);
                break;
            case 'reset':
                $this->reset($neo4j);
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->line('Available actions: stats, cleanup, reset');

                return 1;
        }

        return 0;
    }

    private function showStats(Neo4jService $neo4j)
    {
        $this->info('📊 Neo4j Database Statistics');
        $this->line('');

        try {
            // Node statistics
            $nodeStats = $neo4j->runQuery('
                MATCH (n) 
                RETURN labels(n) as labels, count(n) as count 
                ORDER BY count DESC
            ');

            $this->line('🏷️  Node Labels:');
            foreach ($nodeStats as $stat) {
                $labels = $stat->get('labels');
                $count = $stat->get('count');

                // Handle Neo4j CypherList type
                if ($labels instanceof \Laudis\Neo4j\Types\CypherList) {
                    $labelsArray = $labels->toArray();
                    $labelStr = empty($labelsArray) ? '[No Label]' : implode(', ', $labelsArray);
                } else {
                    $labelStr = empty($labels) ? '[No Label]' : implode(', ', $labels);
                }

                $this->line("   {$labelStr}: {$count}");
            }

            // Relationship statistics
            $relStats = $neo4j->runQuery('
                MATCH ()-[r]->() 
                RETURN type(r) as type, count(r) as count 
                ORDER BY count DESC
            ');

            $this->line('');
            $this->line('🔗 Relationship Types:');
            foreach ($relStats as $stat) {
                $type = $stat->get('type');
                $count = $stat->get('count');
                $this->line("   {$type}: {$count}");
            }

            // Total counts
            $totalNodes = $neo4j->runQuery('MATCH (n) RETURN count(n) as count')->first()->get('count');
            $totalRels = $neo4j->runQuery('MATCH ()-[r]->() RETURN count(r) as count')->first()->get('count');

            $this->line('');
            $this->line('📈 Totals:');
            $this->line("   Total Nodes: {$totalNodes}");
            $this->line("   Total Relationships: {$totalRels}");

        } catch (\Exception $e) {
            $this->error("Error getting stats: {$e->getMessage()}");
        }
    }

    private function cleanup(Neo4jService $neo4j)
    {
        if (! $this->confirm('This will remove all orphaned nodes (nodes with no relationships). Continue?')) {
            $this->info('Cleanup cancelled.');

            return;
        }

        $this->info('🧹 Cleaning up orphaned nodes...');

        try {
            $result = $neo4j->runQuery('
                MATCH (n) 
                WHERE NOT (n)--() 
                DELETE n 
                RETURN count(n) as deleted
            ');

            $deleted = $result->first()->get('deleted');
            $this->info("✅ Deleted {$deleted} orphaned nodes.");

        } catch (\Exception $e) {
            $this->error("Error during cleanup: {$e->getMessage()}");
        }
    }

    private function reset(Neo4jService $neo4j)
    {
        if (! $this->confirm('⚠️  This will DELETE ALL DATA in the Neo4j database. Are you sure?')) {
            $this->info('Reset cancelled.');

            return;
        }

        if (! $this->confirm('This action cannot be undone. Really delete everything?')) {
            $this->info('Reset cancelled.');

            return;
        }

        $this->info('🗑️  Resetting Neo4j database...');

        try {
            // Delete all relationships first
            $neo4j->runQuery('MATCH ()-[r]->() DELETE r');

            // Then delete all nodes
            $result = $neo4j->runQuery('MATCH (n) DELETE n RETURN count(n) as deleted');

            $this->info('✅ Database reset completed. All data has been deleted.');

        } catch (\Exception $e) {
            $this->error("Error during reset: {$e->getMessage()}");
        }
    }
}
