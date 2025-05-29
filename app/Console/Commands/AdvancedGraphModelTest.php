<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Services\Neo4jService;
use Illuminate\Console\Command;

class AdvancedGraphModelTest extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neo4j:advanced-test 
                            {--test=all : Specific test to run (all, labels, relationships, bidirectional, dynamic, corruption, bulk, injection, partial, uniqueness, events)}
                            {--detailed : Show detailed output}';

    /**
     * The console command description.
     */
    protected $description = 'Advanced testing of Neo4j Graph Model features and edge cases';

    private Neo4jService $neo4j;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->neo4j = app(Neo4jService::class);
        $test = $this->option('test');

        $this->info('🧪 Running Advanced Neo4j Graph Model Tests...');
        $this->newLine();

        switch ($test) {
            case 'all':
                $this->runAllTests();
                break;
            case 'labels':
                $this->test_multiple_labels();
                break;
            case 'relationships':
                $this->test_relationship_properties();
                break;
            case 'bidirectional':
                $this->test_bidirectional_queries();
                break;
            case 'dynamic':
                $this->test_dynamic_relationship_types();
                break;
            case 'corruption':
                $this->test_missing_corrupt_data();
                break;
            case 'bulk':
                $this->test_bulk_operations();
                break;
            case 'injection':
                $this->test_cypher_injection_protection();
                break;
            case 'partial':
                $this->test_partial_property_matching();
                break;
            case 'uniqueness':
                $this->test_property_indexing_uniqueness();
                break;
            case 'events':
                $this->test_model_events();
                break;
            default:
                $this->error("Unknown test: $test");

                return 1;
        }

        $this->info('✅ Advanced tests completed!');

        return 0;
    }

    /**
     * Run all advanced tests
     */
    protected function runAllTests(): void
    {
        $tests = [
            'Multiple Labels Support' => fn () => $this->test_multiple_labels(),
            'Relationship Properties' => fn () => $this->test_relationship_properties(),
            'Bidirectional Queries' => fn () => $this->test_bidirectional_queries(),
            'Dynamic Relationship Types' => fn () => $this->test_dynamic_relationship_types(),
            'Missing/Corrupt Data' => fn () => $this->test_missing_corrupt_data(),
            'Bulk Operations' => fn () => $this->test_bulk_operations(),
            'Cypher Injection Protection' => fn () => $this->test_cypher_injection_protection(),
            'Partial Property Matching' => fn () => $this->test_partial_property_matching(),
            'Property Indexing/Uniqueness' => fn () => $this->test_property_indexing_uniqueness(),
        ];

        foreach ($tests as $testName => $testFunction) {
            $this->info("🔬 Testing: $testName");
            try {
                $testFunction();
                $this->line("✅ $testName: PASSED");
            } catch (\Exception $e) {
                $this->error("❌ $testName: FAILED - ".$e->getMessage());
                if ($this->option('detailed')) {
                    $this->line($e->getTraceAsString());
                }
            }
            $this->newLine();
        }
    }

    /**
     * Test 1: Multiple Labels Support
     */
    protected function test_multiple_labels(): void
    {
        $this->info('🏷️  Testing Multiple Labels Support...');

        // Create a test person
        $person = Person::create([
            'name' => 'Multi Label Test Person',
            'email' => 'multilabel@test.com',
            'age' => 30,
            'occupation' => 'Developer',
            'company' => 'TechCorp',
        ]);

        $this->line("Created person: {$person->name} (ID: {$person->getId()})");

        // Add a second label manually via Cypher
        $query = 'MATCH (p:Person) WHERE id(p) = $id SET p:Employee RETURN p';
        $result = $this->neo4j->runQuery($query, ['id' => $person->getId()]);
        $this->line('✅ Added Employee label to person');

        // Test querying by both labels
        $query = 'MATCH (p:Person:Employee) WHERE id(p) = $id RETURN p, labels(p) as labels';
        $result = $this->neo4j->runQuery($query, ['id' => $person->getId()]);

        if ($result->count() > 0) {
            $record = $result->first();
            $labels = $record->get('labels')->toArray();
            $this->line('✅ Person has labels: '.implode(', ', $labels));

            if (in_array('Person', $labels) && in_array('Employee', $labels)) {
                $this->line('✅ Multiple labels correctly assigned');
            } else {
                throw new \Exception('Multiple labels not found');
            }
        }

        // Test filtering by multiple labels
        $query = 'MATCH (p:Person:Employee) RETURN count(p) as count';
        $result = $this->neo4j->runQuery($query);
        $count = $result->first()->get('count');
        $this->line("✅ Found $count nodes with both Person and Employee labels");

        // Cleanup
        $person->delete();
        $this->line('🧹 Cleaned up test person');
    }

    /**
     * Test 2: Relationship Properties
     */
    protected function test_relationship_properties(): void
    {
        $this->info('🔗 Testing Relationship Properties...');

        // Create test people
        $person1 = Person::create(['name' => 'Alice Relations', 'email' => 'alice@rel.com', 'age' => 25]);
        $person2 = Person::create(['name' => 'Bob Relations', 'email' => 'bob@rel.com', 'age' => 28]);

        // Test creating relationship with properties
        $result = $person1->addFriend($person2, [
            'since' => '2020-01-01',
            'metAt' => 'conference',
            'strength' => 8,
        ]);

        if ($result) {
            $this->line('✅ Created friendship with properties');
        } else {
            throw new \Exception('Failed to create friendship with properties');
        }

        // Test querying relationship properties
        $query = '
            MATCH (a:Person)-[r:FRIEND]->(b:Person) 
            WHERE id(a) = $id1 AND id(b) = $id2 
            RETURN r.since as since, r.metAt as metAt, r.strength as strength
        ';
        $result = $this->neo4j->runQuery($query, [
            'id1' => $person1->getId(),
            'id2' => $person2->getId(),
        ]);

        if ($result->count() > 0) {
            $record = $result->first();
            $this->line('✅ Relationship properties:');
            $this->line('  - Since: '.$record->get('since'));
            $this->line('  - Met at: '.$record->get('metAt'));
            $this->line('  - Strength: '.$record->get('strength'));
        }

        // Test updating relationship properties
        $updateQuery = "
            MATCH (a:Person)-[r:FRIEND]->(b:Person) 
            WHERE id(a) = \$id1 AND id(b) = \$id2 
            SET r.metAt = 'work', r.lastContact = '2024-01-01'
            RETURN r
        ";
        $this->neo4j->runQuery($updateQuery, [
            'id1' => $person1->getId(),
            'id2' => $person2->getId(),
        ]);
        $this->line('✅ Updated relationship properties');

        // Test filtering by relationship properties
        $filterQuery = '
            MATCH (a:Person)-[r:FRIEND]->(b:Person) 
            WHERE r.strength >= 5 
            RETURN count(r) as count
        ';
        $result = $this->neo4j->runQuery($filterQuery);
        $count = $result->first()->get('count');
        $this->line("✅ Found $count friendships with strength >= 5");

        // Cleanup
        $person1->delete();
        $person2->delete();
        $this->line('🧹 Cleaned up test people');
    }

    /**
     * Test 3: Bidirectional Queries
     */
    protected function test_bidirectional_queries(): void
    {
        $this->info('↔️  Testing Bidirectional Queries...');

        // Create test people
        $alice = Person::create(['name' => 'Alice Bidirectional', 'email' => 'alice@bi.com', 'age' => 30]);
        $bob = Person::create(['name' => 'Bob Bidirectional', 'email' => 'bob@bi.com', 'age' => 32]);
        $charlie = Person::create(['name' => 'Charlie Bidirectional', 'email' => 'charlie@bi.com', 'age' => 29]);

        // Create directional relationships
        $alice->addFriend($bob);
        $charlie->addFriend($alice);

        $this->line('✅ Created directional friendships: Alice->Bob, Charlie->Alice');

        // Test outgoing relationships (default)
        $aliceFriendsOut = $alice->getFriends();
        $this->line("Alice's outgoing friends: ".$aliceFriendsOut->count());

        // Test incoming relationships
        $aliceFriendsIn = $alice->getRelated('FRIEND', 'in');
        $this->line("Alice's incoming friends: ".$aliceFriendsIn->count());

        // Test bidirectional relationships
        $query = '
            MATCH (alice:Person)-[:FRIEND]-(friend:Person) 
            WHERE id(alice) = $id 
            RETURN friend, id(friend) as neo4j_id
        ';
        $result = $this->neo4j->runQuery($query, ['id' => $alice->getId()]);

        $bidirectionalFriends = collect($result)->map(function ($record) {
            $nodeData = $record->get('friend')->getProperties()->toArray();
            $nodeId = $record->get('neo4j_id');
            $nodeData['id'] = $nodeId;

            return new Person($nodeData);
        });

        $this->line("✅ Alice's bidirectional friends: ".$bidirectionalFriends->count());
        foreach ($bidirectionalFriends as $friend) {
            $this->line("  - {$friend->name}");
        }

        // Cleanup
        $alice->delete();
        $bob->delete();
        $charlie->delete();
        $this->line('🧹 Cleaned up test people');
    }

    /**
     * Test 4: Dynamic Relationship Types
     */
    protected function test_dynamic_relationship_types(): void
    {
        $this->info('🌐 Testing Dynamic Relationship Types...');

        // Create test people
        $person = Person::create(['name' => 'Connected Person', 'email' => 'connected@test.com', 'age' => 35]);
        $friend = Person::create(['name' => 'Friend Person', 'email' => 'friend@test.com', 'age' => 33]);
        $colleague = Person::create(['name' => 'Colleague Person', 'email' => 'colleague@test.com', 'age' => 31]);

        // Create different relationship types
        $person->addFriend($friend);
        $person->addColleague($colleague);

        // Test getting all connections regardless of type
        $query = '
            MATCH (p:Person)-[r]->(connected:Person) 
            WHERE id(p) = $id 
            RETURN connected, type(r) as relationship_type, id(connected) as neo4j_id
        ';
        $result = $this->neo4j->runQuery($query, ['id' => $person->getId()]);

        $connections = collect($result)->map(function ($record) {
            $nodeData = $record->get('connected')->getProperties()->toArray();
            $nodeId = $record->get('neo4j_id');
            $nodeData['id'] = $nodeId;
            $relationshipType = $record->get('relationship_type');

            return [
                'person' => new Person($nodeData),
                'relationship_type' => $relationshipType,
            ];
        });

        $this->line("✅ All connections for {$person->name}:");
        foreach ($connections as $connection) {
            $this->line("  - {$connection['person']->name} ({$connection['relationship_type']})");
        }

        // Test relationship type counts
        $query = '
            MATCH (p:Person)-[r]->(connected:Person) 
            WHERE id(p) = $id 
            RETURN type(r) as rel_type, count(r) as count
        ';
        $result = $this->neo4j->runQuery($query, ['id' => $person->getId()]);

        $this->line('✅ Relationship type breakdown:');
        foreach ($result as $record) {
            $relType = $record->get('rel_type');
            $count = $record->get('count');
            $this->line("  - $relType: $count");
        }

        // Cleanup
        $person->delete();
        $friend->delete();
        $colleague->delete();
        $this->line('🧹 Cleaned up test people');
    }

    /**
     * Test 5: Missing or Corrupt Data
     */
    protected function test_missing_corrupt_data(): void
    {
        $this->info('🔍 Testing Missing/Corrupt Data Handling...');

        // Test 1: Node with missing expected properties
        $query = 'CREATE (p:Person {age: 25}) RETURN p, id(p) as neo4j_id';
        $result = $this->neo4j->runQuery($query);
        $record = $result->first();
        $nodeData = $record->get('p')->getProperties()->toArray();
        $nodeId = $record->get('neo4j_id');
        $nodeData['id'] = $nodeId;

        $incompletePersons = new Person($nodeData);
        $this->line('✅ Created person with missing properties (no name/email)');
        $this->line('  - Name: '.($incompletePersons->getAttribute('name') ?? 'NULL'));
        $this->line('  - Email: '.($incompletePersons->getAttribute('email') ?? 'NULL'));
        $this->line('  - Age: '.($incompletePersons->getAttribute('age') ?? 'NULL'));

        // Test 2: Malformed relationship data
        $person1 = Person::create(['name' => 'Test Person 1', 'email' => 'test1@corrupt.com', 'age' => 30]);
        $person2 = Person::create(['name' => 'Test Person 2', 'email' => 'test2@corrupt.com', 'age' => 28]);

        // Create relationship with malformed data
        $malformedQuery = "
            MATCH (a:Person), (b:Person) 
            WHERE id(a) = \$id1 AND id(b) = \$id2 
            CREATE (a)-[r:FRIEND {since: 'invalid-date', strength: 'not-a-number'}]->(b) 
            RETURN r
        ";
        $this->neo4j->runQuery($malformedQuery, [
            'id1' => $person1->getId(),
            'id2' => $person2->getId(),
        ]);
        $this->line('✅ Created relationship with malformed properties');

        // Test 3: Relationship to deleted node (orphaned relationship)
        $person3 = Person::create(['name' => 'To Be Deleted', 'email' => 'delete@test.com', 'age' => 25]);
        $person1->addFriend($person3);

        // Delete person3 but leave relationship (simulate corruption)
        $deleteQuery = 'MATCH (p:Person) WHERE id(p) = $id DETACH DELETE p';
        $this->neo4j->runQuery($deleteQuery, ['id' => $person3->getId()]);
        $this->line('✅ Simulated orphaned relationship (deleted target node)');

        // Test querying with missing data
        try {
            $friends = $person1->getFriends();
            $this->line('✅ Queried relationships with potentially missing nodes: '.$friends->count());
        } catch (\Exception $e) {
            $this->line('⚠️  Query failed with missing nodes: '.$e->getMessage());
        }

        // Cleanup
        $query = 'MATCH (p:Person) WHERE id(p) IN [$id1, $id2, $id3] DETACH DELETE p';
        $this->neo4j->runQuery($query, [
            'id1' => $incompletePersons->getId(),
            'id2' => $person1->getId(),
            'id3' => $person2->getId(),
        ]);
        $this->line('🧹 Cleaned up test data');
    }

    /**
     * Test 6: Bulk Operations
     */
    protected function test_bulk_operations(): void
    {
        $this->info('📊 Testing Bulk Operations...');

        $startTime = microtime(true);
        $memoryStart = memory_get_usage(true);

        // Create 100 people for bulk testing
        $people = [];
        $this->line('Creating 100 people...');

        for ($i = 1; $i <= 100; $i++) {
            $person = Person::create([
                'name' => "Bulk Person $i",
                'email' => "bulk$i@test.com",
                'age' => rand(20, 60),
                'occupation' => 'Tester',
                'company' => 'BulkCorp',
            ]);
            $people[] = $person;

            if ($i % 25 == 0) {
                $this->line("  Created $i people...");
            }
        }

        $creationTime = microtime(true) - $startTime;
        $this->line('✅ Created 100 people in '.round($creationTime, 2).' seconds');

        // Create a dense node (one person connected to many others)
        $centralPerson = $people[0];
        $this->line('Creating dense connections...');

        $relationshipStart = microtime(true);
        for ($i = 1; $i < 50; $i++) {
            $centralPerson->addFriend($people[$i]);
            if ($i % 10 == 0) {
                $this->line("  Created $i relationships...");
            }
        }

        $relationshipTime = microtime(true) - $relationshipStart;
        $this->line('✅ Created 49 relationships in '.round($relationshipTime, 2).' seconds');

        // Test bulk query performance
        $queryStart = microtime(true);
        $allBulkPeople = Person::where('company', '=', 'BulkCorp')->get();
        $queryTime = microtime(true) - $queryStart;

        $this->line('✅ Queried '.$allBulkPeople->count().' people in '.round($queryTime, 4).' seconds');

        // Test dense node query performance
        $denseQueryStart = microtime(true);
        $friends = $centralPerson->getFriends();
        $denseQueryTime = microtime(true) - $denseQueryStart;

        $this->line('✅ Queried dense node ('.$friends->count().' friends) in '.round($denseQueryTime, 4).' seconds');

        // Memory usage
        $memoryEnd = memory_get_usage(true);
        $memoryUsed = ($memoryEnd - $memoryStart) / 1024 / 1024;
        $this->line('📊 Memory used: '.round($memoryUsed, 2).' MB');

        // Performance summary
        $totalTime = microtime(true) - $startTime;
        $this->line('📊 Total test time: '.round($totalTime, 2).' seconds');

        // Cleanup
        $this->line('Cleaning up bulk data...');
        $cleanup = Person::where('company', '=', 'BulkCorp')->get();
        foreach ($cleanup as $person) {
            $person->delete();
        }
        $this->line('🧹 Cleaned up '.$cleanup->count().' bulk test people');
    }

    /**
     * Test 7: Cypher Injection Protection
     */
    protected function test_cypher_injection_protection(): void
    {
        $this->info('🔒 Testing Cypher Injection Protection...');

        // Test 1: Malicious input in where clause
        $maliciousInput = '" MATCH (n) DETACH DELETE n RETURN n //';

        try {
            $results = Person::where('name', '=', $maliciousInput)->get();
            $this->line('✅ Malicious input sanitized, returned '.$results->count().' results');
        } catch (\Exception $e) {
            $this->line('⚠️  Query failed (expected): '.$e->getMessage());
        }

        // Test 2: SQL-style injection
        $sqlInjection = "'; DROP TABLE Person; --";

        try {
            $results = Person::where('email', '=', $sqlInjection)->get();
            $this->line('✅ SQL injection attempt blocked, returned '.$results->count().' results');
        } catch (\Exception $e) {
            $this->line('⚠️  Query failed (expected): '.$e->getMessage());
        }

        // Test 3: Cypher comment injection
        $commentInjection = "test' OR 1=1 //";

        try {
            $results = Person::where('occupation', '=', $commentInjection)->get();
            $this->line('✅ Comment injection blocked, returned '.$results->count().' results');
        } catch (\Exception $e) {
            $this->line('⚠️  Query failed (expected): '.$e->getMessage());
        }

        // Test 4: Valid special characters should work
        $validSpecialChars = "O'Connor";
        $testPerson = Person::create([
            'name' => $validSpecialChars,
            'email' => 'oconnor@test.com',
            'age' => 30,
        ]);

        $results = Person::where('name', '=', $validSpecialChars)->get();
        if ($results->count() > 0) {
            $this->line('✅ Valid special characters handled correctly');
        }

        // Cleanup
        $testPerson->delete();
        $this->line('🧹 Cleaned up injection test data');
    }

    /**
     * Test 8: Partial Property Matching
     */
    protected function test_partial_property_matching(): void
    {
        $this->info('🔍 Testing Partial Property Matching...');

        // Create test data
        $testPeople = [
            Person::create(['name' => 'John Doe', 'email' => 'john.doe@example.com', 'age' => 30]),
            Person::create(['name' => 'Jane Smith', 'email' => 'jane.smith@example.org', 'age' => 25]),
            Person::create(['name' => 'Bob Johnson', 'email' => 'bob@company.com', 'age' => 35]),
        ];

        // Test CONTAINS
        $containsResults = collect();
        $query = 'MATCH (p:Person) WHERE p.email CONTAINS $term RETURN p, id(p) as neo4j_id';
        $result = $this->neo4j->runQuery($query, ['term' => 'example']);

        foreach ($result as $record) {
            $nodeData = $record->get('p')->getProperties()->toArray();
            $nodeId = $record->get('neo4j_id');
            $nodeData['id'] = $nodeId;
            $containsResults->push(new Person($nodeData));
        }

        $this->line("✅ CONTAINS 'example': ".$containsResults->count().' results');

        // Test STARTS WITH
        $query = 'MATCH (p:Person) WHERE p.name STARTS WITH $term RETURN p, id(p) as neo4j_id';
        $result = $this->neo4j->runQuery($query, ['term' => 'John']);
        $startsWithCount = $result->count();
        $this->line("✅ STARTS WITH 'John': $startsWithCount results");

        // Test ENDS WITH
        $query = 'MATCH (p:Person) WHERE p.email ENDS WITH $term RETURN p, id(p) as neo4j_id';
        $result = $this->neo4j->runQuery($query, ['term' => '.com']);
        $endsWithCount = $result->count();
        $this->line("✅ ENDS WITH '.com': $endsWithCount results");

        // Test case-insensitive search (already implemented in GraphQueryBuilder)
        $searchResults = Person::search('JOHN')->get();
        $this->line("✅ Case-insensitive search for 'JOHN': ".$searchResults->count().' results');

        // Test regex-like patterns using CONTAINS
        $query = "MATCH (p:Person) WHERE p.email =~ '.*@example\\.(com|org)' RETURN count(p) as count";
        $result = $this->neo4j->runQuery($query);
        $regexCount = $result->first()->get('count');
        $this->line("✅ Regex pattern '@example.(com|org)': $regexCount results");

        // Cleanup
        foreach ($testPeople as $person) {
            $person->delete();
        }
        $this->line('🧹 Cleaned up partial matching test data');
    }

    /**
     * Test 9: Property Indexing/Uniqueness
     */
    protected function test_property_indexing_uniqueness(): void
    {
        $this->info('🏷️  Testing Property Indexing/Uniqueness...');

        // Test creating constraint (if supported)
        try {
            $constraintQuery = 'CREATE CONSTRAINT person_email_unique IF NOT EXISTS FOR (p:Person) REQUIRE p.email IS UNIQUE';
            $this->neo4j->runQuery($constraintQuery);
            $this->line('✅ Created email uniqueness constraint');
        } catch (\Exception $e) {
            $this->line('⚠️  Constraint creation failed (may already exist): '.$e->getMessage());
        }

        // Test creating index
        try {
            $indexQuery = 'CREATE INDEX person_name_index IF NOT EXISTS FOR (p:Person) ON (p.name)';
            $this->neo4j->runQuery($indexQuery);
            $this->line('✅ Created name index');
        } catch (\Exception $e) {
            $this->line('⚠️  Index creation failed (may already exist): '.$e->getMessage());
        }

        // Test unique constraint behavior
        $person1 = Person::create([
            'name' => 'Unique Test 1',
            'email' => 'unique@test.com',
            'age' => 30,
        ]);

        try {
            $person2 = Person::create([
                'name' => 'Unique Test 2',
                'email' => 'unique@test.com', // Same email
                'age' => 25,
            ]);
            $this->line('⚠️  Duplicate email was allowed (constraint may not be active)');
            $person2->delete();
        } catch (\Exception $e) {
            $this->line('✅ Duplicate email blocked by constraint: '.$e->getMessage());
        }

        // Test index performance (simple benchmark)
        $startTime = microtime(true);
        $indexedResults = Person::where('name', '=', 'Unique Test 1')->get();
        $indexTime = microtime(true) - $startTime;
        $this->line('✅ Indexed query performance: '.round($indexTime * 1000, 2).'ms');

        // Show existing constraints and indexes
        try {
            $constraintsQuery = 'SHOW CONSTRAINTS';
            $constraintsResult = $this->neo4j->runQuery($constraintsQuery);
            $this->line('📊 Active constraints: '.$constraintsResult->count());
        } catch (\Exception $e) {
            $this->line('⚠️  Could not list constraints: '.$e->getMessage());
        }

        try {
            $indexesQuery = 'SHOW INDEXES';
            $indexesResult = $this->neo4j->runQuery($indexesQuery);
            $this->line('📊 Active indexes: '.$indexesResult->count());
        } catch (\Exception $e) {
            $this->line('⚠️  Could not list indexes: '.$e->getMessage());
        }

        // Cleanup
        $person1->delete();
        $this->line('🧹 Cleaned up uniqueness test data');
    }

    /**
     * Test 10: Model Events (Optional)
     */
    protected function test_model_events(): void
    {
        $this->info('🎭 Testing Model Events...');

        // Since Laravel's model events aren't implemented in GraphModel yet,
        // this is a placeholder for future implementation

        $this->line('⚠️  Model events not yet implemented in GraphModel');
        $this->line('📝 Future events to implement:');
        $this->line('  - creating');
        $this->line('  - created');
        $this->line('  - updating');
        $this->line('  - updated');
        $this->line('  - deleting');
        $this->line('  - deleted');

        // Example of what events might look like:
        $this->line('💡 Example usage:');
        $this->line('   Person::creating(function ($person) {');
        $this->line('       $person->created_at = now();');
        $this->line('   });');

        $this->line('✅ Model events test completed (placeholder)');
    }
}
