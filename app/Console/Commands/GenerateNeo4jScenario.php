<?php

namespace App\Console\Commands;

use App\Services\Neo4jFactoryService;
use Illuminate\Console\Command;

class GenerateNeo4jScenario extends Command
{
    protected $signature = 'neo4j:generate {scenario : The scenario to generate (small_company|family_tree|academic_network|social_circle|startup_ecosystem|university_campus|sports_league|multinational_corp|creative_agency|research_institute)} {--fresh : Clear existing data first}';

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
                
            case 'startup_ecosystem':
                $this->line('   🚀 Startup Ecosystem:');
                $this->line('      • Founders, investors, advisors');
                $this->line('      • Development and business teams');
                $this->line('      • Mentorship and funding relationships');
                break;
                
            case 'university_campus':
                $this->line('   🎓 University Campus:');
                $this->line('      • Faculty, grad students, undergrads');
                $this->line('      • Academic mentorship hierarchy');
                $this->line('      • Student friendship networks');
                break;
                
            case 'sports_league':
                $this->line('   ⚽ Sports League:');
                $this->line('      • Teams, coaches, players');
                $this->line('      • League management structure');
                $this->line('      • Team dynamics and rivalries');
                break;
                
            case 'multinational_corp':
                $this->line('   🌍 Multinational Corporation:');
                $this->line('      • Global CEO, regional VPs');
                $this->line('      • Multi-level management hierarchy');
                $this->line('      • Cross-regional collaborations');
                break;
                
            case 'creative_agency':
                $this->line('   🎨 Creative Agency:');
                $this->line('      • Creative teams, account managers');
                $this->line('      • Project-based collaborations');
                $this->line('      • Freelancer networks');
                break;
                
            case 'research_institute':
                $this->line('   🔬 Research Institute:');
                $this->line('      • Research groups, visiting scholars');
                $this->line('      • Academic collaboration networks');
                $this->line('      • Mentorship hierarchies');
                break;
        }
    }
}
