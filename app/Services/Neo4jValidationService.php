<?php

namespace App\Services;

class Neo4jValidationService
{
    protected $neo4j;

    protected $validationRules = [];

    protected $errors = [];

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
        $this->setupValidationRules();
    }

    /**
     * Setup validation rules for data quality
     */
    protected function setupValidationRules(): void
    {
        $this->validationRules = [
            'person_data_quality' => [
                'required_fields' => ['name', 'email'],
                'email_format' => true,
                'age_range' => [0, 120],
                'no_duplicate_emails' => true,
            ],
            'relationship_integrity' => [
                'no_self_relationships' => true,
                'valid_relationship_types' => [
                    'FRIENDS_WITH', 'WORKS_WITH', 'MANAGES', 'REPORTS_TO',
                    'FAMILY_OF', 'MARRIED_TO', 'COLLABORATES_WITH', 'MENTORS',
                    'KNOWS', 'STUDIED_WITH',
                ],
                'strength_range' => [1, 10],
            ],
            'network_structure' => [
                'no_isolated_nodes' => false, // Allow isolated nodes
                'max_degree' => 50, // Maximum connections per person
                'check_orphaned_relationships' => true,
            ],
        ];
    }

    /**
     * Validate entire database
     */
    public function validateDatabase(): array
    {
        $this->errors = [];

        $this->validatePersonData();
        $this->validateRelationshipIntegrity();
        $this->validateNetworkStructure();

        return [
            'status' => empty($this->errors) ? 'valid' : 'invalid',
            'errors' => $this->errors,
            'error_count' => count($this->errors),
            'validation_timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Validate person data quality
     */
    protected function validatePersonData(): void
    {
        // Check for required fields
        $query = '
            MATCH (p:Person)
            WHERE p.name IS NULL OR p.name = "" OR p.email IS NULL OR p.email = ""
            RETURN p, ID(p) as nodeId
        ';

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $nodeId = $record->get('nodeId');
            $this->errors[] = [
                'type' => 'missing_required_field',
                'node_id' => $nodeId,
                'message' => 'Person node missing required name or email field',
            ];
        }

        // Check email format
        $query = '
            MATCH (p:Person)
            WHERE p.email IS NOT NULL 
              AND NOT p.email =~ "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
            RETURN p.name as name, p.email as email, ID(p) as nodeId
        ';

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $this->errors[] = [
                'type' => 'invalid_email_format',
                'node_id' => $record->get('nodeId'),
                'name' => $record->get('name'),
                'email' => $record->get('email'),
                'message' => 'Invalid email format',
            ];
        }

        // Check age range
        $query = '
            MATCH (p:Person)
            WHERE p.age IS NOT NULL AND (p.age < 0 OR p.age > 120)
            RETURN p.name as name, p.age as age, ID(p) as nodeId
        ';

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $this->errors[] = [
                'type' => 'invalid_age_range',
                'node_id' => $record->get('nodeId'),
                'name' => $record->get('name'),
                'age' => $record->get('age'),
                'message' => 'Age outside valid range (0-120)',
            ];
        }

        // Check for duplicate emails
        $query = '
            MATCH (p:Person)
            WHERE p.email IS NOT NULL
            WITH p.email as email, collect(p) as persons
            WHERE size(persons) > 1
            RETURN email, persons
        ';

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $email = $record->get('email');
            $persons = $record->get('persons');

            $this->errors[] = [
                'type' => 'duplicate_email',
                'email' => $email,
                'count' => count($persons),
                'message' => "Email {$email} is used by multiple persons",
            ];
        }
    }

    /**
     * Validate relationship integrity
     */
    protected function validateRelationshipIntegrity(): void
    {
        // Check for self-relationships
        $query = '
            MATCH (p:Person)-[r]->(p)
            RETURN p.name as name, type(r) as relType, ID(p) as nodeId, ID(r) as relId
        ';

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $this->errors[] = [
                'type' => 'self_relationship',
                'node_id' => $record->get('nodeId'),
                'relationship_id' => $record->get('relId'),
                'name' => $record->get('name'),
                'relationship_type' => $record->get('relType'),
                'message' => 'Person has relationship with themselves',
            ];
        }

        // Check for invalid relationship types
        $validTypes = $this->validationRules['relationship_integrity']['valid_relationship_types'];
        $typesList = "'".implode("', '", $validTypes)."'";

        $query = "
            MATCH ()-[r]->()
            WHERE NOT type(r) IN [{$typesList}]
            RETURN DISTINCT type(r) as invalidType, count(r) as count
        ";

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $this->errors[] = [
                'type' => 'invalid_relationship_type',
                'relationship_type' => $record->get('invalidType'),
                'count' => $record->get('count'),
                'message' => 'Invalid relationship type found',
            ];
        }

        // Check relationship strength range
        $query = '
            MATCH ()-[r]->()
            WHERE r.strength IS NOT NULL AND (r.strength < 1 OR r.strength > 10)
            RETURN type(r) as relType, r.strength as strength, ID(r) as relId
        ';

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $this->errors[] = [
                'type' => 'invalid_strength_range',
                'relationship_id' => $record->get('relId'),
                'relationship_type' => $record->get('relType'),
                'strength' => $record->get('strength'),
                'message' => 'Relationship strength outside valid range (1-10)',
            ];
        }

        // Check for orphaned relationships (relationships with missing nodes)
        $query = '
            MATCH ()-[r]->()
            OPTIONAL MATCH (start)-[r]->(end)
            WHERE start IS NULL OR end IS NULL
            RETURN r, ID(r) as relId
        ';

        $result = $this->neo4j->runQuery($query);
        foreach ($result as $record) {
            $this->errors[] = [
                'type' => 'orphaned_relationship',
                'relationship_id' => $record->get('relId'),
                'message' => 'Relationship exists without valid start or end nodes',
            ];
        }
    }

    /**
     * Validate network structure
     */
    protected function validateNetworkStructure(): void
    {
        // Check for nodes with too many connections
        $maxDegree = $this->validationRules['network_structure']['max_degree'];

        $query = '
            MATCH (p:Person)
            OPTIONAL MATCH (p)-[r]-()
            WITH p, count(r) as degree
            WHERE degree > $maxDegree
            RETURN p.name as name, degree, ID(p) as nodeId
        ';

        $result = $this->neo4j->runQuery($query, ['maxDegree' => $maxDegree]);
        foreach ($result as $record) {
            $this->errors[] = [
                'type' => 'excessive_connections',
                'node_id' => $record->get('nodeId'),
                'name' => $record->get('name'),
                'degree' => $record->get('degree'),
                'max_allowed' => $maxDegree,
                'message' => "Person has too many connections ({$record->get('degree')} > {$maxDegree})",
            ];
        }

        // Check for isolated nodes (if configured)
        if ($this->validationRules['network_structure']['no_isolated_nodes']) {
            $query = '
                MATCH (p:Person)
                WHERE NOT (p)-[]-()
                RETURN p.name as name, ID(p) as nodeId
            ';

            $result = $this->neo4j->runQuery($query);
            foreach ($result as $record) {
                $this->errors[] = [
                    'type' => 'isolated_node',
                    'node_id' => $record->get('nodeId'),
                    'name' => $record->get('name'),
                    'message' => 'Person has no connections',
                ];
            }
        }
    }

    /**
     * Fix common data quality issues
     */
    public function fixDataQualityIssues(): array
    {
        $fixed = [];

        // Fix empty names by generating placeholder names
        $query = '
            MATCH (p:Person)
            WHERE p.name IS NULL OR p.name = ""
            SET p.name = "Person_" + toString(ID(p))
            RETURN count(p) as fixed
        ';

        $result = $this->neo4j->runQuery($query);
        $fixedNames = $result->first()->get('fixed');
        if ($fixedNames > 0) {
            $fixed[] = "Fixed {$fixedNames} missing names";
        }

        // Remove self-relationships
        $query = '
            MATCH (p:Person)-[r]->(p)
            DELETE r
            RETURN count(r) as fixed
        ';

        $result = $this->neo4j->runQuery($query);
        $fixedSelfRels = $result->first()->get('fixed');
        if ($fixedSelfRels > 0) {
            $fixed[] = "Removed {$fixedSelfRels} self-relationships";
        }

        // Fix relationship strengths outside valid range
        $query = '
            MATCH ()-[r]->()
            WHERE r.strength IS NOT NULL AND r.strength < 1
            SET r.strength = 1
            RETURN count(r) as fixed
        ';

        $result = $this->neo4j->runQuery($query);
        $fixedLowStrength = $result->first()->get('fixed');

        $query = '
            MATCH ()-[r]->()
            WHERE r.strength IS NOT NULL AND r.strength > 10
            SET r.strength = 10
            RETURN count(r) as fixed
        ';

        $result = $this->neo4j->runQuery($query);
        $fixedHighStrength = $result->first()->get('fixed');

        $totalStrengthFixed = $fixedLowStrength + $fixedHighStrength;
        if ($totalStrengthFixed > 0) {
            $fixed[] = "Fixed {$totalStrengthFixed} relationship strength values";
        }

        return [
            'status' => 'completed',
            'fixes_applied' => $fixed,
            'total_fixes' => count($fixed),
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * Generate data quality report
     */
    public function generateQualityReport(): array
    {
        $validation = $this->validateDatabase();

        // Get quality metrics
        $metrics = $this->getQualityMetrics();

        return [
            'validation_results' => $validation,
            'quality_metrics' => $metrics,
            'quality_score' => $this->calculateQualityScore($validation, $metrics),
            'recommendations' => $this->generateRecommendations($validation, $metrics),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get data quality metrics
     */
    protected function getQualityMetrics(): array
    {
        // Completeness metrics
        $query = '
            MATCH (p:Person)
            RETURN 
                count(p) as totalPersons,
                count(p.name) as personsWithName,
                count(p.email) as personsWithEmail,
                count(p.age) as personsWithAge,
                count(p.phone) as personsWithPhone,
                count(p.occupation) as personsWithOccupation
        ';

        $result = $this->neo4j->runQuery($query);
        $completeness = $result->first();

        $total = $completeness->get('totalPersons');

        return [
            'completeness' => [
                'name' => round(($completeness->get('personsWithName') / $total) * 100, 2),
                'email' => round(($completeness->get('personsWithEmail') / $total) * 100, 2),
                'age' => round(($completeness->get('personsWithAge') / $total) * 100, 2),
                'phone' => round(($completeness->get('personsWithPhone') / $total) * 100, 2),
                'occupation' => round(($completeness->get('personsWithOccupation') / $total) * 100, 2),
            ],
            'consistency' => $this->getConsistencyMetrics(),
            'validity' => $this->getValidityMetrics(),
        ];
    }

    /**
     * Get consistency metrics
     */
    protected function getConsistencyMetrics(): array
    {
        // Check email format consistency
        $query = '
            MATCH (p:Person)
            WHERE p.email IS NOT NULL
            WITH count(p) as totalEmails,
                 count(CASE WHEN p.email =~ "^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$" THEN 1 END) as validEmails
            RETURN toFloat(validEmails) / totalEmails * 100 as emailConsistency
        ';

        $result = $this->neo4j->runQuery($query);
        $emailConsistency = $result->first()->get('emailConsistency') ?? 100;

        return [
            'email_format' => round($emailConsistency, 2),
        ];
    }

    /**
     * Get validity metrics
     */
    protected function getValidityMetrics(): array
    {
        // Check age validity
        $query = '
            MATCH (p:Person)
            WHERE p.age IS NOT NULL
            WITH count(p) as totalAges,
                 count(CASE WHEN p.age >= 0 AND p.age <= 120 THEN 1 END) as validAges
            RETURN toFloat(validAges) / totalAges * 100 as ageValidity
        ';

        $result = $this->neo4j->runQuery($query);
        $ageValidity = $result->first()->get('ageValidity') ?? 100;

        return [
            'age_range' => round($ageValidity, 2),
        ];
    }

    /**
     * Calculate overall quality score
     */
    protected function calculateQualityScore(array $validation, array $metrics): float
    {
        $errorCount = $validation['error_count'];
        $completenessScore = array_sum($metrics['completeness']) / count($metrics['completeness']);
        $consistencyScore = array_sum($metrics['consistency']) / count($metrics['consistency']);
        $validityScore = array_sum($metrics['validity']) / count($metrics['validity']);

        // Penalize for errors
        $errorPenalty = min($errorCount * 5, 50); // Max 50% penalty

        $overallScore = ($completenessScore + $consistencyScore + $validityScore) / 3;
        $finalScore = max(0, $overallScore - $errorPenalty);

        return round($finalScore, 2);
    }

    /**
     * Generate recommendations based on validation results
     */
    protected function generateRecommendations(array $validation, array $metrics): array
    {
        $recommendations = [];

        if ($validation['error_count'] > 0) {
            $recommendations[] = "Fix {$validation['error_count']} data validation errors using the auto-fix feature";
        }

        if ($metrics['completeness']['email'] < 80) {
            $recommendations[] = "Improve email completeness (currently {$metrics['completeness']['email']}%)";
        }

        if ($metrics['completeness']['age'] < 70) {
            $recommendations[] = "Add age information for more persons (currently {$metrics['completeness']['age']}%)";
        }

        if ($metrics['consistency']['email_format'] < 95) {
            $recommendations[] = "Fix email format issues (currently {$metrics['consistency']['email_format']}% valid)";
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Data quality is good! Consider adding more detailed person attributes';
        }

        return $recommendations;
    }
}
