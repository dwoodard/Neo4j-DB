<?php

namespace App\Console\Commands;

use Database\Seeders\Neo4jSeeder;
use Illuminate\Console\Command;

class SeedNeo4j extends Command
{
    protected $signature = 'neo4j:seed {--fresh : Clear existing data before seeding}';

    protected $description = 'Seed the Neo4j database with sample data using factories';

    public function handle()
    {
        if ($this->option('fresh')) {
            if ($this->confirm('⚠️  This will delete all existing data in Neo4j. Continue?')) {
                $this->call('neo4j:maintenance', ['action' => 'reset']);
            } else {
                $this->info('Seeding cancelled.');
                return 1;
            }
        }

        $this->info('🚀 Starting Neo4j database seeding...');
        $this->line('');

        $seeder = new Neo4jSeeder();
        $seeder->setCommand($this);
        $seeder->run();

        $this->line('');
        $this->info('✨ Use the following commands to explore your data:');
        $this->line('   • php artisan neo4j:maintenance stats  - View database statistics');
        $this->line('   • Visit http://localhost:8000/neo4j-demo  - Interactive web interface');
        $this->line('   • Visit http://localhost:7474  - Neo4j Browser');

        return 0;
    }
}
