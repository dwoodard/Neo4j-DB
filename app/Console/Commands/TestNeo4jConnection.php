<?php

namespace App\Console\Commands;

use App\Services\Neo4jService;
use Exception;
use Illuminate\Console\Command;

class TestNeo4jConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'neo4j:test {--create-sample : Create sample data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Neo4j database connection and run sample queries';

    /**
     * Execute the console command.
     */
    public function handle(Neo4jService $neo4j)
    {
        $this->info('🔍 Testing Neo4j Connection...');
        $this->newLine();

        // Display connection details
        $this->table(['Setting', 'Value'], [
            ['Host', config('services.neo4j.host')],
            ['Port', config('services.neo4j.port')],
            ['Username', config('services.neo4j.username')],
            ['Password', config('services.neo4j.password') ? '***' : 'Not set'],
        ]);

        $this->newLine();

        // Test basic connection
        $this->info('1. Testing basic connection...');
        if ($neo4j->testConnection()) {
            $this->info('✅ Connection successful!');
        } else {
            $this->error('❌ Connection failed!');

            return 1;
        }

        $this->newLine();

        // Test database info
        $this->info('2. Getting database information...');
        try {
            $result = $neo4j->runQuery('CALL dbms.components() YIELD name, versions, edition');
            foreach ($result as $record) {
                $this->info("Database: {$record->get('name')} {$record->get('versions')[0]} ({$record->get('edition')})");
            }
        } catch (Exception $e) {
            $this->warn("Could not get database info: {$e->getMessage()}");
        }

        $this->newLine();

        // Count existing nodes
        $this->info('3. Counting existing nodes...');
        try {
            $result = $neo4j->runQuerySingle('MATCH (n) RETURN count(n) as nodeCount');
            $nodeCount = $result->get('nodeCount');
            $this->info("Total nodes in database: {$nodeCount}");
        } catch (Exception $e) {
            $this->error("Failed to count nodes: {$e->getMessage()}");
        }

        $this->newLine();

        // Create sample data if requested
        if ($this->option('create-sample')) {
            $this->info('4. Creating sample data...');
            $this->createSampleData($neo4j);
        }

        // Test query execution
        $this->info('5. Testing query execution...');
        try {
            $result = $neo4j->runQuery('RETURN "Hello from Neo4j!" as message, datetime() as timestamp');
            $record = $result->first();
            $this->info("Message: {$record->get('message')}");
            
            // Handle Neo4j DateTime object
            $timestamp = $record->get('timestamp');
            if ($timestamp instanceof \Laudis\Neo4j\Types\DateTime) {
                // Convert to PHP DateTime and format
                $dateTime = $timestamp->toDateTime();
                $this->info("Timestamp: {$dateTime->format('Y-m-d H:i:s')}");
            } else {
                $this->info("Timestamp: {$timestamp}");
            }
        } catch (Exception $e) {
            $this->error("Query test failed: {$e->getMessage()}");
        }

        $this->newLine();
        $this->info('🎉 Neo4j testing completed!');

        return 0;
    }

    private function createSampleData(Neo4jService $neo4j)
    {
        try {
            // Create sample users
            $neo4j->runQuery('
                CREATE (alice:User {name: "Alice", email: "alice@example.com", age: 30})
                CREATE (bob:User {name: "Bob", email: "bob@example.com", age: 25})
                CREATE (charlie:User {name: "Charlie", email: "charlie@example.com", age: 35})
                CREATE (alice)-[:FRIENDS_WITH]->(bob)
                CREATE (bob)-[:FRIENDS_WITH]->(charlie)
                CREATE (alice)-[:KNOWS]->(charlie)
            ');

            $this->info('✅ Sample users and relationships created');

            // Create sample posts
            $neo4j->runQuery('
                MATCH (alice:User {name: "Alice"}), (bob:User {name: "Bob"})
                CREATE (post1:Post {title: "Hello Neo4j!", content: "Learning graph databases", createdAt: datetime()})
                CREATE (post2:Post {title: "Laravel + Neo4j", content: "Building awesome apps", createdAt: datetime()})
                CREATE (alice)-[:AUTHORED]->(post1)
                CREATE (bob)-[:AUTHORED]->(post2)
                CREATE (alice)-[:LIKES]->(post2)
            ');

            $this->info('✅ Sample posts created');

            // Show what was created
            $result = $neo4j->runQuery('
                MATCH (u:User)-[r:AUTHORED]->(p:Post)
                RETURN u.name as author, p.title as title
            ');

            $this->table(['Author', 'Post Title'],
                collect($result)->map(fn ($record) => [
                    $record->get('author'),
                    $record->get('title'),
                ])->toArray()
            );

        } catch (Exception $e) {
            $this->error("Failed to create sample data: {$e->getMessage()}");
        }
    }
}
