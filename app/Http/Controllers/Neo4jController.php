<?php

namespace App\Http\Controllers;

use App\Services\Neo4jService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Neo4jController extends Controller
{
    protected $neo4j;

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
    }

    /**
     * Display Neo4j database information
     */
    public function index(): JsonResponse
    {
        try {
            // Get database info
            $result = $this->neo4j->runQuery('CALL dbms.components()');
            $dbInfo = $result->first();
            
            // Count nodes
            $countResult = $this->neo4j->runQuery('MATCH (n) RETURN count(n) as nodeCount');
            $nodeCount = $countResult->first()->get('nodeCount');
            
            return response()->json([
                'status' => 'success',
                'database' => [
                    'name' => $dbInfo->get('name'),
                    'versions' => $dbInfo->get('versions'),
                    'edition' => $dbInfo->get('edition'),
                ],
                'nodeCount' => $nodeCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new person node
     */
    public function createPerson(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|integer|min:0',
            'email' => 'nullable|email',
        ]);

        try {
            $query = '
                CREATE (p:Person {
                    name: $name,
                    age: $age,
                    email: $email,
                    created_at: datetime()
                })
                RETURN p
            ';

            $result = $this->neo4j->runQuery($query, [
                'name' => $request->name,
                'age' => $request->age,
                'email' => $request->email,
            ]);

            $person = $result->first()->get('p');
            
            return response()->json([
                'status' => 'success',
                'message' => 'Person created successfully',
                'person' => [
                    'id' => $person->getId(),
                    'labels' => $person->getLabels(),
                    'properties' => $person->getProperties(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all persons
     */
    public function getPersons(): JsonResponse
    {
        try {
            $query = 'MATCH (p:Person) RETURN p ORDER BY p.name';
            $result = $this->neo4j->runQuery($query);

            $persons = [];
            foreach ($result as $record) {
                $person = $record->get('p');
                $persons[] = [
                    'id' => $person->getId(),
                    'properties' => $person->getProperties(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'persons' => $persons,
                'count' => count($persons),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a relationship between two persons
     */
    public function createRelationship(Request $request): JsonResponse
    {
        $request->validate([
            'person1_id' => 'required|integer',
            'person2_id' => 'required|integer',
            'relationship_type' => 'required|string|max:50',
        ]);

        try {
            $query = '
                MATCH (p1:Person), (p2:Person)
                WHERE ID(p1) = $person1_id AND ID(p2) = $person2_id
                CREATE (p1)-[r:' . strtoupper($request->relationship_type) . ' {
                    created_at: datetime()
                }]->(p2)
                RETURN p1, r, p2
            ';

            $result = $this->neo4j->runQuery($query, [
                'person1_id' => $request->person1_id,
                'person2_id' => $request->person2_id,
            ]);

            if ($result->count() === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'One or both persons not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Relationship created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get network graph data
     */
    public function getNetwork(): JsonResponse
    {
        try {
            $query = '
                MATCH (p1:Person)-[r]->(p2:Person)
                RETURN p1, r, p2
            ';

            $result = $this->neo4j->runQuery($query);

            $nodes = [];
            $relationships = [];
            $nodeIds = [];

            foreach ($result as $record) {
                $person1 = $record->get('p1');
                $person2 = $record->get('p2');
                $relationship = $record->get('r');

                // Add nodes if not already added
                $p1Id = $person1->getId();
                $p2Id = $person2->getId();

                if (!in_array($p1Id, $nodeIds)) {
                    $nodes[] = [
                        'id' => $p1Id,
                        'label' => $person1->getProperty('name'),
                        'properties' => $person1->getProperties(),
                    ];
                    $nodeIds[] = $p1Id;
                }

                if (!in_array($p2Id, $nodeIds)) {
                    $nodes[] = [
                        'id' => $p2Id,
                        'label' => $person2->getProperty('name'),
                        'properties' => $person2->getProperties(),
                    ];
                    $nodeIds[] = $p2Id;
                }

                // Add relationship
                $relationships[] = [
                    'id' => $relationship->getId(),
                    'source' => $p1Id,
                    'target' => $p2Id,
                    'type' => $relationship->getType(),
                    'properties' => $relationship->getProperties(),
                ];
            }

            return response()->json([
                'status' => 'success',
                'network' => [
                    'nodes' => $nodes,
                    'relationships' => $relationships,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
