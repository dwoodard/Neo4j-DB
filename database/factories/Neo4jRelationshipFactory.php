<?php

namespace Database\Factories;

use App\Services\Neo4jService;
use Faker\Factory as FakerFactory;
use Faker\Generator as Faker;

class Neo4jRelationshipFactory
{
    protected $neo4j;
    protected $faker;
    protected $state = [];
    protected $relationshipTypes = [
        'FRIENDS_WITH',
        'WORKS_WITH',
        'MANAGES',
        'REPORTS_TO',
        'FAMILY_OF',
        'MARRIED_TO',
        'COLLABORATES_WITH',
        'MENTORS',
        'KNOWS',
        'STUDIED_WITH'
    ];

    public function __construct(Neo4jService $neo4j = null)
    {
        $this->neo4j = $neo4j ?: app(Neo4jService::class);
        $this->faker = FakerFactory::create();
    }

    /**
     * Define the relationship's default state.
     */
    public function definition(): array
    {
        return [
            'type' => $this->faker->randomElement($this->relationshipTypes),
            'strength' => $this->faker->numberBetween(1, 10),
            'since' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'notes' => $this->faker->optional(0.7)->sentence(),
        ];
    }

    /**
     * Create a relationship between two persons in Neo4j
     */
    public function create(array $attributes = []): array
    {
        $data = array_merge($this->definition(), $this->state, $attributes);
        
        // Ensure we have person IDs
        if (!isset($data['person1_id']) || !isset($data['person2_id'])) {
            throw new \InvalidArgumentException('Both person1_id and person2_id are required to create a relationship');
        }

        $query = '
            MATCH (p1:Person), (p2:Person)
            WHERE ID(p1) = $person1_id AND ID(p2) = $person2_id
            CREATE (p1)-[r:' . $data['type'] . ' {
                strength: $strength,
                since: date($since),
                notes: $notes,
                created_at: datetime(),
                updated_at: datetime()
            }]->(p2)
            RETURN r, p1.name as person1_name, p2.name as person2_name
        ';

        $result = $this->neo4j->runQuery($query, [
            'person1_id' => $data['person1_id'],
            'person2_id' => $data['person2_id'],
            'strength' => $data['strength'],
            'since' => $data['since'],
            'notes' => $data['notes'],
        ]);

        if ($result->count() === 0) {
            throw new \RuntimeException('Failed to create relationship - one or both persons not found');
        }

        $record = $result->first();
        $relationship = $record->get('r');

        return [
            'id' => $relationship->getId(),
            'type' => $relationship->getType(),
            'properties' => $relationship->getProperties(),
            'person1_name' => $record->get('person1_name'),
            'person2_name' => $record->get('person2_name'),
        ];
    }

    /**
     * Create relationship between specific persons
     */
    public function between(int $person1Id, int $person2Id): self
    {
        return $this->state([
            'person1_id' => $person1Id,
            'person2_id' => $person2Id,
        ]);
    }

    /**
     * Create a friendship relationship
     */
    public function friendship(): self
    {
        return $this->state([
            'type' => 'FRIENDS_WITH',
            'strength' => $this->faker->numberBetween(6, 10),
            'notes' => $this->faker->randomElement([
                'Met at university',
                'Childhood friends',
                'Met through mutual friends',
                'Neighbors',
                'Gym buddies',
                'Travel companions'
            ]),
        ]);
    }

    /**
     * Create a work relationship
     */
    public function workRelationship(): self
    {
        return $this->state([
            'type' => $this->faker->randomElement(['WORKS_WITH', 'COLLABORATES_WITH']),
            'strength' => $this->faker->numberBetween(4, 8),
            'notes' => $this->faker->randomElement([
                'Same department',
                'Cross-team collaboration',
                'Project partners',
                'Office mates',
                'Remote colleagues'
            ]),
        ]);
    }

    /**
     * Create a management relationship
     */
    public function management(): self
    {
        return $this->state([
            'type' => $this->faker->randomElement(['MANAGES', 'REPORTS_TO']),
            'strength' => $this->faker->numberBetween(5, 9),
            'notes' => $this->faker->randomElement([
                'Direct report',
                'Team lead relationship',
                'Department management',
                'Cross-functional oversight'
            ]),
        ]);
    }

    /**
     * Create a family relationship
     */
    public function family(): self
    {
        return $this->state([
            'type' => 'FAMILY_OF',
            'strength' => $this->faker->numberBetween(8, 10),
            'notes' => $this->faker->randomElement([
                'Siblings',
                'Parent-child',
                'Cousins',
                'Uncle/Aunt relationship',
                'Grandparent-grandchild'
            ]),
        ]);
    }

    /**
     * Create a marriage relationship
     */
    public function marriage(): self
    {
        return $this->state([
            'type' => 'MARRIED_TO',
            'strength' => $this->faker->numberBetween(8, 10),
            'since' => $this->faker->dateTimeBetween('-20 years', '-1 year')->format('Y-m-d'),
            'notes' => 'Married couple',
        ]);
    }

    /**
     * Create a mentorship relationship
     */
    public function mentorship(): self
    {
        return $this->state([
            'type' => 'MENTORS',
            'strength' => $this->faker->numberBetween(6, 9),
            'notes' => $this->faker->randomElement([
                'Career mentorship',
                'Technical mentoring',
                'Leadership coaching',
                'Industry guidance'
            ]),
        ]);
    }

    /**
     * Create an academic relationship
     */
    public function academic(): self
    {
        return $this->state([
            'type' => 'STUDIED_WITH',
            'strength' => $this->faker->numberBetween(5, 8),
            'since' => $this->faker->dateTimeBetween('-15 years', '-5 years')->format('Y-m-d'),
            'notes' => $this->faker->randomElement([
                'University classmates',
                'Study group partners',
                'Research collaborators',
                'Lab partners',
                'Thesis committee'
            ]),
        ]);
    }

    /**
     * Create a weak relationship
     */
    public function weak(): self
    {
        return $this->state([
            'type' => 'KNOWS',
            'strength' => $this->faker->numberBetween(1, 4),
            'notes' => $this->faker->randomElement([
                'Acquaintances',
                'Met briefly',
                'Online connection',
                'Conference contact',
                'Distant connection'
            ]),
        ]);
    }

    /**
     * Create a strong relationship
     */
    public function strong(): self
    {
        return $this->state([
            'strength' => $this->faker->numberBetween(8, 10),
        ]);
    }

    /**
     * Create a recent relationship
     */
    public function recent(): self
    {
        return $this->state([
            'since' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Create an old relationship
     */
    public function old(): self
    {
        return $this->state([
            'since' => $this->faker->dateTimeBetween('-20 years', '-5 years')->format('Y-m-d'),
        ]);
    }

    /**
     * Get all available persons from the database
     */
    public function getAvailablePersons(): array
    {
        $result = $this->neo4j->runQuery('MATCH (p:Person) RETURN ID(p) as id, p.name as name');
        $persons = [];
        
        foreach ($result as $record) {
            $persons[] = [
                'id' => $record->get('id'),
                'name' => $record->get('name'),
            ];
        }
        
        return $persons;
    }

    /**
     * Create relationships between random existing persons
     */
    public function createBetweenRandomPersons(int $count = 1): array
    {
        $persons = $this->getAvailablePersons();
        
        if (count($persons) < 2) {
            throw new \RuntimeException('Need at least 2 persons in the database to create relationships');
        }

        $relationships = [];
        
        for ($i = 0; $i < $count; $i++) {
            // Pick two different random persons
            $person1 = $this->faker->randomElement($persons);
            do {
                $person2 = $this->faker->randomElement($persons);
            } while ($person2['id'] === $person1['id']);

            $relationships[] = $this->between($person1['id'], $person2['id'])->create();
        }

        return $count === 1 ? $relationships[0] : $relationships;
    }

    /**
     * Set state for the factory
     */
    public function state(array $state): self
    {
        $this->state = array_merge($this->state, $state);
        return $this;
    }
}
