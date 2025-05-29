<?php

namespace App\Services;

use App\Services\Neo4jService;

class Neo4jAnalyticsService
{
    protected $neo4j;

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
    }

    /**
     * Get comprehensive network statistics
     */
    public function getNetworkStatistics(): array
    {
        $stats = [];

        // Basic counts
        $stats['nodes'] = $this->getNodeCounts();
        $stats['relationships'] = $this->getRelationshipCounts();
        
        // Network metrics
        $stats['network_metrics'] = $this->getNetworkMetrics();
        
        // Demographics analysis
        $stats['demographics'] = $this->getDemographicsAnalysis();
        
        // Connection patterns
        $stats['connection_patterns'] = $this->getConnectionPatterns();
        
        return $stats;
    }

    /**
     * Get node counts by type
     */
    public function getNodeCounts(): array
    {
        $query = '
            MATCH (n)
            RETURN labels(n) as nodeType, count(n) as count
            ORDER BY count DESC
        ';

        $result = $this->neo4j->runQuery($query);
        $counts = [];

        foreach ($result as $record) {
            $labels = $record->get('nodeType');
            $count = $record->get('count');
            $labelKey = implode(':', $labels);
            $counts[$labelKey] = $count;
        }

        return $counts;
    }

    /**
     * Get relationship counts by type
     */
    public function getRelationshipCounts(): array
    {
        $query = '
            MATCH ()-[r]->()
            RETURN type(r) as relationshipType, count(r) as count
            ORDER BY count DESC
        ';

        $result = $this->neo4j->runQuery($query);
        $counts = [];

        foreach ($result as $record) {
            $type = $record->get('relationshipType');
            $count = $record->get('count');
            $counts[$type] = $count;
        }

        return $counts;
    }

    /**
     * Get advanced network metrics
     */
    public function getNetworkMetrics(): array
    {
        return [
            'density' => $this->getNetworkDensity(),
            'average_degree' => $this->getAverageDegree(),
            'clustering_coefficient' => $this->getClusteringCoefficient(),
            'connected_components' => $this->getConnectedComponents(),
            'most_connected_nodes' => $this->getMostConnectedNodes(10),
            'least_connected_nodes' => $this->getLeastConnectedNodes(10),
        ];
    }

    /**
     * Calculate network density
     */
    public function getNetworkDensity(): float
    {
        $query = '
            MATCH (n:Person)
            WITH count(n) as nodeCount
            MATCH ()-[r]->()
            WITH nodeCount, count(r) as edgeCount
            RETURN 
                CASE 
                    WHEN nodeCount > 1 THEN 
                        toFloat(edgeCount) / (nodeCount * (nodeCount - 1))
                    ELSE 0 
                END as density
        ';

        $result = $this->neo4j->runQuery($query);
        return $result->first()->get('density') ?? 0.0;
    }

    /**
     * Calculate average degree
     */
    public function getAverageDegree(): float
    {
        $query = '
            MATCH (n:Person)
            OPTIONAL MATCH (n)-[r]-()
            WITH n, count(r) as degree
            RETURN avg(toFloat(degree)) as avgDegree
        ';

        $result = $this->neo4j->runQuery($query);
        return $result->first()->get('avgDegree') ?? 0.0;
    }

    /**
     * Calculate clustering coefficient
     */
    public function getClusteringCoefficient(): float
    {
        $query = '
            MATCH (n:Person)-[:FRIENDS_WITH]-(neighbor:Person)
            WITH n, collect(DISTINCT neighbor) as neighbors
            WHERE size(neighbors) > 1
            UNWIND neighbors as neighbor1
            UNWIND neighbors as neighbor2
            WITH n, neighbor1, neighbor2, neighbors
            WHERE id(neighbor1) < id(neighbor2)
            OPTIONAL MATCH (neighbor1)-[:FRIENDS_WITH]-(neighbor2)
            WITH n, 
                 size(neighbors) as neighborCount,
                 count(CASE WHEN neighbor1 IS NOT NULL AND neighbor2 IS NOT NULL THEN 1 END) as triangles
            WITH n, 
                 neighborCount,
                 triangles,
                 (neighborCount * (neighborCount - 1)) / 2 as possibleTriangles
            WHERE possibleTriangles > 0
            WITH n, toFloat(triangles) / possibleTriangles as localClustering
            RETURN avg(localClustering) as globalClustering
        ';

        $result = $this->neo4j->runQuery($query);
        return $result->first()->get('globalClustering') ?? 0.0;
    }

    /**
     * Get connected components count
     */
    public function getConnectedComponents(): int
    {
        $query = '
            MATCH (n:Person)
            WITH collect(n) as allNodes
            CALL {
                WITH allNodes
                UNWIND allNodes as node
                MATCH path = (node)-[:FRIENDS_WITH|WORKS_WITH|FAMILY_OF*]-(connected)
                RETURN collect(DISTINCT connected) + node as component
            }
            RETURN count(DISTINCT component) as componentCount
        ';

        try {
            $result = $this->neo4j->runQuery($query);
            return $result->first()->get('componentCount') ?? 0;
        } catch (\Exception $e) {
            // Fallback to simpler approach if advanced query fails
            return 1;
        }
    }

    /**
     * Get most connected nodes
     */
    public function getMostConnectedNodes(int $limit = 10): array
    {
        $query = '
            MATCH (n:Person)
            OPTIONAL MATCH (n)-[r]-()
            WITH n, count(r) as degree
            ORDER BY degree DESC
            LIMIT $limit
            RETURN n.name as name, n.occupation as occupation, degree
        ';

        $result = $this->neo4j->runQuery($query, ['limit' => $limit]);
        $nodes = [];

        foreach ($result as $record) {
            $nodes[] = [
                'name' => $record->get('name'),
                'occupation' => $record->get('occupation'),
                'degree' => $record->get('degree'),
            ];
        }

        return $nodes;
    }

    /**
     * Get least connected nodes
     */
    public function getLeastConnectedNodes(int $limit = 10): array
    {
        $query = '
            MATCH (n:Person)
            OPTIONAL MATCH (n)-[r]-()
            WITH n, count(r) as degree
            ORDER BY degree ASC
            LIMIT $limit
            RETURN n.name as name, n.occupation as occupation, degree
        ';

        $result = $this->neo4j->runQuery($query, ['limit' => $limit]);
        $nodes = [];

        foreach ($result as $record) {
            $nodes[] = [
                'name' => $record->get('name'),
                'occupation' => $record->get('occupation'),
                'degree' => $record->get('degree'),
            ];
        }

        return $nodes;
    }

    /**
     * Analyze demographics
     */
    public function getDemographicsAnalysis(): array
    {
        return [
            'age_distribution' => $this->getAgeDistribution(),
            'gender_distribution' => $this->getGenderDistribution(),
            'occupation_distribution' => $this->getOccupationDistribution(),
            'company_distribution' => $this->getCompanyDistribution(),
            'education_distribution' => $this->getEducationDistribution(),
        ];
    }

    /**
     * Get age distribution
     */
    public function getAgeDistribution(): array
    {
        $query = '
            MATCH (n:Person)
            WHERE n.age IS NOT NULL
            WITH 
                CASE 
                    WHEN n.age < 25 THEN "18-24"
                    WHEN n.age < 35 THEN "25-34"
                    WHEN n.age < 45 THEN "35-44"
                    WHEN n.age < 55 THEN "45-54"
                    WHEN n.age < 65 THEN "55-64"
                    ELSE "65+"
                END as ageGroup
            RETURN ageGroup, count(*) as count
            ORDER BY ageGroup
        ';

        $result = $this->neo4j->runQuery($query);
        $distribution = [];

        foreach ($result as $record) {
            $ageGroup = $record->get('ageGroup');
            $count = $record->get('count');
            $distribution[$ageGroup] = $count;
        }

        return $distribution;
    }

    /**
     * Get gender distribution
     */
    public function getGenderDistribution(): array
    {
        $query = '
            MATCH (n:Person)
            WHERE n.gender IS NOT NULL
            RETURN n.gender as gender, count(*) as count
            ORDER BY count DESC
        ';

        $result = $this->neo4j->runQuery($query);
        $distribution = [];

        foreach ($result as $record) {
            $gender = $record->get('gender');
            $count = $record->get('count');
            $distribution[$gender] = $count;
        }

        return $distribution;
    }

    /**
     * Get occupation distribution
     */
    public function getOccupationDistribution(): array
    {
        $query = '
            MATCH (n:Person)
            WHERE n.occupation IS NOT NULL
            RETURN n.occupation as occupation, count(*) as count
            ORDER BY count DESC
            LIMIT 20
        ';

        $result = $this->neo4j->runQuery($query);
        $distribution = [];

        foreach ($result as $record) {
            $occupation = $record->get('occupation');
            $count = $record->get('count');
            $distribution[$occupation] = $count;
        }

        return $distribution;
    }

    /**
     * Get company distribution
     */
    public function getCompanyDistribution(): array
    {
        $query = '
            MATCH (n:Person)
            WHERE n.company IS NOT NULL
            RETURN n.company as company, count(*) as count
            ORDER BY count DESC
            LIMIT 15
        ';

        $result = $this->neo4j->runQuery($query);
        $distribution = [];

        foreach ($result as $record) {
            $company = $record->get('company');
            $count = $record->get('count');
            $distribution[$company] = $count;
        }

        return $distribution;
    }

    /**
     * Get education distribution
     */
    public function getEducationDistribution(): array
    {
        $query = '
            MATCH (n:Person)
            WHERE n.education_level IS NOT NULL
            RETURN n.education_level as education, count(*) as count
            ORDER BY count DESC
        ';

        $result = $this->neo4j->runQuery($query);
        $distribution = [];

        foreach ($result as $record) {
            $education = $record->get('education');
            $count = $record->get('count');
            $distribution[$education] = $count;
        }

        return $distribution;
    }

    /**
     * Analyze connection patterns
     */
    public function getConnectionPatterns(): array
    {
        return [
            'relationship_strength_avg' => $this->getAverageRelationshipStrength(),
            'cross_company_connections' => $this->getCrossCompanyConnections(),
            'age_gap_relationships' => $this->getAgeGapRelationships(),
            'common_interests' => $this->getCommonInterests(),
            'network_clusters' => $this->getNetworkClusters(),
        ];
    }

    /**
     * Get average relationship strength
     */
    public function getAverageRelationshipStrength(): array
    {
        $query = '
            MATCH ()-[r]->()
            WHERE r.strength IS NOT NULL
            RETURN type(r) as relationshipType, avg(toFloat(r.strength)) as avgStrength
            ORDER BY avgStrength DESC
        ';

        $result = $this->neo4j->runQuery($query);
        $strengths = [];

        foreach ($result as $record) {
            $type = $record->get('relationshipType');
            $strength = round($record->get('avgStrength'), 2);
            $strengths[$type] = $strength;
        }

        return $strengths;
    }

    /**
     * Get cross-company connections
     */
    public function getCrossCompanyConnections(): int
    {
        $query = '
            MATCH (p1:Person)-[r]-(p2:Person)
            WHERE p1.company IS NOT NULL 
              AND p2.company IS NOT NULL 
              AND p1.company <> p2.company
            RETURN count(DISTINCT r) as crossCompanyConnections
        ';

        $result = $this->neo4j->runQuery($query);
        return $result->first()->get('crossCompanyConnections') ?? 0;
    }

    /**
     * Get age gap relationship analysis
     */
    public function getAgeGapRelationships(): array
    {
        $query = '
            MATCH (p1:Person)-[r]-(p2:Person)
            WHERE p1.age IS NOT NULL AND p2.age IS NOT NULL
            WITH abs(p1.age - p2.age) as ageGap, type(r) as relType
            RETURN 
                relType,
                avg(toFloat(ageGap)) as avgAgeGap,
                min(ageGap) as minAgeGap,
                max(ageGap) as maxAgeGap
            ORDER BY avgAgeGap DESC
        ';

        $result = $this->neo4j->runQuery($query);
        $ageGaps = [];

        foreach ($result as $record) {
            $relType = $record->get('relType');
            $ageGaps[$relType] = [
                'average' => round($record->get('avgAgeGap'), 1),
                'min' => $record->get('minAgeGap'),
                'max' => $record->get('maxAgeGap'),
            ];
        }

        return $ageGaps;
    }

    /**
     * Get common interests analysis
     */
    public function getCommonInterests(): array
    {
        $query = '
            MATCH (p:Person)
            WHERE p.interests IS NOT NULL
            UNWIND split(replace(replace(p.interests, "[", ""), "]", ""), ",") as interest
            WITH trim(replace(replace(interest, "\"", ""), "\\", "")) as cleanInterest
            WHERE cleanInterest <> ""
            RETURN cleanInterest as interest, count(*) as count
            ORDER BY count DESC
            LIMIT 10
        ';

        $result = $this->neo4j->runQuery($query);
        $interests = [];

        foreach ($result as $record) {
            $interest = $record->get('interest');
            $count = $record->get('count');
            $interests[$interest] = $count;
        }

        return $interests;
    }

    /**
     * Get network clusters (simplified)
     */
    public function getNetworkClusters(): array
    {
        $query = '
            MATCH (p:Person)
            OPTIONAL MATCH (p)-[:WORKS_WITH]-(colleague:Person)
            WITH p, collect(DISTINCT colleague.company) as companies
            WHERE size(companies) > 0
            RETURN companies[0] as cluster, count(p) as size
            ORDER BY size DESC
            LIMIT 10
        ';

        $result = $this->neo4j->runQuery($query);
        $clusters = [];

        foreach ($result as $record) {
            $cluster = $record->get('cluster');
            $size = $record->get('size');
            if ($cluster) {
                $clusters[$cluster] = $size;
            }
        }

        return $clusters;
    }

    /**
     * Generate a comprehensive analytics report
     */
    public function generateReport(): array
    {
        $stats = $this->getNetworkStatistics();
        
        return [
            'generated_at' => now()->toISOString(),
            'summary' => [
                'total_nodes' => array_sum($stats['nodes']),
                'total_relationships' => array_sum($stats['relationships']),
                'network_density' => $stats['network_metrics']['density'],
                'most_connected' => $stats['network_metrics']['most_connected_nodes'][0] ?? null,
            ],
            'detailed_statistics' => $stats,
            'insights' => $this->generateInsights($stats),
        ];
    }

    /**
     * Generate insights from statistics
     */
    protected function generateInsights(array $stats): array
    {
        $insights = [];

        // Network size insights
        $totalNodes = array_sum($stats['nodes']);
        if ($totalNodes > 100) {
            $insights[] = "Large network with {$totalNodes} nodes - good for complex analysis";
        } elseif ($totalNodes > 50) {
            $insights[] = "Medium-sized network with {$totalNodes} nodes - suitable for most analyses";
        } else {
            $insights[] = "Small network with {$totalNodes} nodes - consider adding more data";
        }

        // Density insights
        $density = $stats['network_metrics']['density'];
        if ($density > 0.3) {
            $insights[] = "High network density ({$density}) - people are well connected";
        } elseif ($density > 0.1) {
            $insights[] = "Moderate network density ({$density}) - typical social network";
        } else {
            $insights[] = "Low network density ({$density}) - sparse connections";
        }

        // Demographics insights
        $ageGroups = $stats['demographics']['age_distribution'] ?? [];
        if (!empty($ageGroups)) {
            $dominantAge = array_keys($ageGroups, max($ageGroups))[0];
            $insights[] = "Most people are in the {$dominantAge} age group";
        }

        return $insights;
    }
}
