<?php

namespace App\Services;

class Neo4jBatchService
{
    protected $neo4j;

    protected $batchSize = 100;

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
    }

    /**
     * Set the batch size for operations
     */
    public function setBatchSize(int $size): self
    {
        $this->batchSize = $size;

        return $this;
    }

    /**
     * Create multiple persons in batches for better performance
     */
    public function createPersonsBatch(array $personsData): array
    {
        $results = [];
        $batches = array_chunk($personsData, $this->batchSize);

        foreach ($batches as $batch) {
            $params = [];
            $createClauses = [];

            foreach ($batch as $index => $personData) {
                $createClauses[] = "CREATE (p{$index}:Person {
                    name: \$name{$index},
                    email: \$email{$index},
                    age: \$age{$index},
                    phone: \$phone{$index},
                    address: \$address{$index},
                    occupation: \$occupation{$index},
                    company: \$company{$index},
                    bio: \$bio{$index},
                    created_at: datetime(),
                    updated_at: datetime()
                })";

                foreach ($personData as $key => $value) {
                    $params["{$key}{$index}"] = $value;
                }
            }

            $query = implode("\n", $createClauses)."\nRETURN ".
                     implode(', ', array_map(fn ($i) => "p{$i}", array_keys($batch)));

            $result = $this->neo4j->runQuery($query, $params);

            foreach ($result->first()->values() as $person) {
                $results[] = [
                    'id' => $person->getId(),
                    'properties' => $person->getProperties(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create multiple relationships in batches
     */
    public function createRelationshipsBatch(array $relationshipsData): array
    {
        $results = [];
        $batches = array_chunk($relationshipsData, $this->batchSize);

        foreach ($batches as $batch) {
            $params = [];
            $matchClauses = [];
            $createClauses = [];

            foreach ($batch as $index => $relData) {
                $matchClauses[] = "MATCH (p1_{$index}:Person), (p2_{$index}:Person) 
                                  WHERE ID(p1_{$index}) = \$person1_id{$index} AND ID(p2_{$index}) = \$person2_id{$index}";

                $createClauses[] = "CREATE (p1_{$index})-[r{$index}:{$relData['type']} {
                    strength: \$strength{$index},
                    since: \$since{$index},
                    notes: \$notes{$index},
                    created_at: datetime()
                }]->(p2_{$index})";

                $params["person1_id{$index}"] = $relData['person1_id'];
                $params["person2_id{$index}"] = $relData['person2_id'];
                $params["strength{$index}"] = $relData['strength'] ?? 5;
                $params["since{$index}"] = $relData['since'] ?? date('Y-m-d');
                $params["notes{$index}"] = $relData['notes'] ?? null;
            }

            $query = implode("\n", $matchClauses)."\n".
                     implode("\n", $createClauses)."\nRETURN ".
                     implode(', ', array_map(fn ($i) => "r{$i}", array_keys($batch)));

            $result = $this->neo4j->runQuery($query, $params);

            foreach ($result->first()->values() as $relationship) {
                $results[] = [
                    'id' => $relationship->getId(),
                    'type' => $relationship->getType(),
                    'properties' => $relationship->getProperties(),
                ];
            }
        }

        return $results;
    }

    /**
     * Create a network with optimized batch operations
     */
    public function createNetworkBatch(array $persons, array $relationships): array
    {
        // Create all persons first
        $createdPersons = $this->createPersonsBatch($persons);

        // Map old indices to new IDs
        $personIdMap = array_combine(
            array_keys($persons),
            array_column($createdPersons, 'id')
        );

        // Update relationship data with actual IDs
        $relationshipsWithIds = array_map(function ($rel) use ($personIdMap) {
            return array_merge($rel, [
                'person1_id' => $personIdMap[$rel['person1_index']] ?? $rel['person1_id'],
                'person2_id' => $personIdMap[$rel['person2_index']] ?? $rel['person2_id'],
            ]);
        }, $relationships);

        // Create all relationships
        $createdRelationships = $this->createRelationshipsBatch($relationshipsWithIds);

        return [
            'persons' => $createdPersons,
            'relationships' => $createdRelationships,
        ];
    }

    /**
     * Bulk delete nodes and relationships
     */
    public function bulkDelete(?string $nodeType = null): void
    {
        if ($nodeType) {
            $query = "MATCH (n:{$nodeType}) DETACH DELETE n";
        } else {
            $query = 'MATCH (n) DETACH DELETE n';
        }

        $this->neo4j->runQuery($query);
    }

    /**
     * Create indexes for better performance
     */
    public function createIndexes(): void
    {
        $indexes = [
            'CREATE INDEX person_name_index IF NOT EXISTS FOR (p:Person) ON (p.name)',
            'CREATE INDEX person_email_index IF NOT EXISTS FOR (p:Person) ON (p.email)',
            'CREATE INDEX person_age_index IF NOT EXISTS FOR (p:Person) ON (p.age)',
            'CREATE INDEX person_occupation_index IF NOT EXISTS FOR (p:Person) ON (p.occupation)',
            'CREATE INDEX person_company_index IF NOT EXISTS FOR (p:Person) ON (p.company)',
        ];

        foreach ($indexes as $indexQuery) {
            try {
                $this->neo4j->runQuery($indexQuery);
            } catch (\Exception $e) {
                // Index might already exist, continue
            }
        }
    }
}
