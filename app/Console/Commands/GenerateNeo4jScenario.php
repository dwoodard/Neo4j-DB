<?php

namespace App\Console\Commands;

use App\Services\Neo4jFactoryService;
use Illuminate\Console\Command;

class GenerateNeo4jScenario extends Command
{
    protected $signature = 'neo4j:generate {scenario : The scenario to generate (small_company|family_tree|academic_network|social_circle)} {--fresh : Clear existing data first}';

    protected $description = 'Generate specific Neo4j network scenarios using factories';

    public function handle(Neo4jFactoryService $factoryService)
    {
        $scenario = $this->argument('scenario');
        
        if ($this->option('fresh')) {
            if ($this->confirm('⚠️  This will delete all existing data in Neo4j. Continue?')) {
                $this->call('neo4j:maintenance', ['action' => 'reset']);
            } else {
                $this->info('Generation cancelled.');
                return 1;
            }
        }

        $this->info("🎬 Generating '{$scenario}' scenario...");
        $this->line('');

        try {
            $result = $factoryService->createNetworkScenario($scenario);
            
            $personCount = count($result['persons']);
            $relationshipCount = count($result['relationships']);
            
            $this->info("✅ Scenario '{$scenario}' generated successfully!");
            $this->line("   👤 Created {$personCount} persons");
            $this->line("   🔗 Created {$relationshipCount} relationships");
            
            $this->line('');
            $this->info('🎯 Scenario Details:');
            $this->describeScenario($scenario);
            
            $this->line('');
            $this->info('✨ Explore your data:');
            $this->line('   • php artisan neo4j:maintenance stats  - View statistics');
            $this->line('   • Visit http://localhost:8000/neo4j-demo  - Web interface');
            $this->line('   • Visit http://localhost:7474  - Neo4j Browser');
            
        } catch (\Exception $e) {
            $this->error("Failed to generate scenario: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }

    private function describeScenario(string $scenario): void
    {
        switch ($scenario) {
            case 'small_company':
                $this->line('   🏢 Small Company Network:');
                $this->line('      • CEO, VPs, Engineers, Sales team');
                $this->line('      • Management hierarchy relationships');
                $this->line('      • Peer collaboration relationships');
                break;
                
            case 'family_tree':
                $this->line('   👨‍👩‍👧‍👦 Family Tree Network:');
                $this->line('      • Grandparents, parents, children');
                $this->line('      • Marriage and family relationships');
                $this->line('      • Multi-generational connections');
                break;
                
            case 'academic_network':
                $this->line('   🎓 Academic Network:');
                $this->line('      • Professor, PhD students, Masters students');
                $this->line('      • Mentorship relationships');
                $this->line('      • Academic collaboration');
                break;
                
            case 'social_circle':
                $this->line('   👥 Social Circle Network:');
                $this->line('      • Group of young friends');
                $this->line('      • Dense friendship connections');
                $this->line('      • Everyone knows everyone pattern');
                break;
        }
    }
}
