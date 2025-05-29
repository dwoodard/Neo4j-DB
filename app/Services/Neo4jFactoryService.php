<?php

namespace App\Services;

use Database\Factories\Neo4jPersonFactory;
use Database\Factories\Neo4jRelationshipFactory;

class Neo4jFactoryService
{
    protected $neo4j;
    protected $personFactory;
    protected $relationshipFactory;

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
        $this->personFactory = new Neo4jPersonFactory(neo4j: $neo4j);
        $this->relationshipFactory = new Neo4jRelationshipFactory(neo4j: $neo4j);
    }

    /**
     * Get the person factory instance
     */
    public function person(): Neo4jPersonFactory
    {
        return $this->personFactory;
    }

    /**
     * Get the relationship factory instance
     */
    public function relationship(): Neo4jRelationshipFactory
    {
        return $this->relationshipFactory;
    }

    /**
     * Create a complete network scenario
     */
    public function createNetworkScenario(string $scenario): array
    {
        switch ($scenario) {
            case 'small_company':
                return $this->createSmallCompanyNetwork();
            case 'family_tree':
                return $this->createFamilyTree();
            case 'academic_network':
                return $this->createAcademicNetwork();
            case 'social_circle':
                return $this->createSocialCircle();
            default:
                throw new \InvalidArgumentException("Unknown scenario: {$scenario}");
        }
    }

    /**
     * Create a small company network
     */
    protected function createSmallCompanyNetwork(): array
    {
        $persons = [];
        $relationships = [];

        // CEO
        $ceo = $this->person()->withName('Sarah Mitchell')->withOccupation('CEO', 'TechStart Inc.')->senior()->create();
        $persons[] = $ceo;

        // VPs
        $vpEng = $this->person()->withName('Michael Chen')->withOccupation('VP Engineering', 'TechStart Inc.')->middleAged()->create();
        $vpSales = $this->person()->withName('Jessica Rodriguez')->withOccupation('VP Sales', 'TechStart Inc.')->middleAged()->create();
        $persons[] = $vpEng;
        $persons[] = $vpSales;

        // Engineering team
        $engineers = [];
        for ($i = 0; $i < 4; $i++) {
            $engineer = $this->person()->techWorker()->withOccupation('Software Engineer', 'TechStart Inc.')->young()->create();
            $engineers[] = $engineer;
            $persons[] = $engineer;
        }

        // Sales team
        $salesReps = [];
        for ($i = 0; $i < 3; $i++) {
            $salesRep = $this->person()->businessPro()->withOccupation('Sales Representative', 'TechStart Inc.')->create();
            $salesReps[] = $salesRep;
            $persons[] = $salesRep;
        }

        // Create management relationships
        $relationships[] = $this->relationship()->between($ceo['id'], $vpEng['id'])->management()->create();
        $relationships[] = $this->relationship()->between($ceo['id'], $vpSales['id'])->management()->create();

        foreach ($engineers as $engineer) {
            $relationships[] = $this->relationship()->between($vpEng['id'], $engineer['id'])->management()->create();
        }

        foreach ($salesReps as $salesRep) {
            $relationships[] = $this->relationship()->between($vpSales['id'], $salesRep['id'])->management()->create();
        }

        // Create peer relationships
        for ($i = 0; $i < count($engineers) - 1; $i++) {
            $relationships[] = $this->relationship()->between($engineers[$i]['id'], $engineers[$i + 1]['id'])->workRelationship()->create();
        }

        return ['persons' => $persons, 'relationships' => $relationships];
    }

    /**
     * Create a family tree
     */
    protected function createFamilyTree(): array
    {
        $persons = [];
        $relationships = [];

        // Grandparents
        $grandpa = $this->person()->withName('Robert Wilson')->senior()->create();
        $grandma = $this->person()->withName('Margaret Wilson')->senior()->create();
        $persons[] = $grandpa;
        $persons[] = $grandma;

        $relationships[] = $this->relationship()->between($grandpa['id'], $grandma['id'])->marriage()->create();

        // Parents
        $dad = $this->person()->withName('John Wilson')->middleAged()->create();
        $mom = $this->person()->withName('Linda Wilson')->middleAged()->create();
        $persons[] = $dad;
        $persons[] = $mom;

        $relationships[] = $this->relationship()->between($dad['id'], $mom['id'])->marriage()->create();
        $relationships[] = $this->relationship()->between($grandpa['id'], $dad['id'])->family()->create();
        $relationships[] = $this->relationship()->between($grandma['id'], $dad['id'])->family()->create();

        // Children
        $child1 = $this->person()->withName('Emma Wilson')->young()->create();
        $child2 = $this->person()->withName('Lucas Wilson')->young()->create();
        $persons[] = $child1;
        $persons[] = $child2;

        $relationships[] = $this->relationship()->between($dad['id'], $child1['id'])->family()->create();
        $relationships[] = $this->relationship()->between($mom['id'], $child1['id'])->family()->create();
        $relationships[] = $this->relationship()->between($dad['id'], $child2['id'])->family()->create();
        $relationships[] = $this->relationship()->between($mom['id'], $child2['id'])->family()->create();
        $relationships[] = $this->relationship()->between($child1['id'], $child2['id'])->family()->create();

        return ['persons' => $persons, 'relationships' => $relationships];
    }

    /**
     * Create an academic network
     */
    protected function createAcademicNetwork(): array
    {
        $persons = [];
        $relationships = [];

        // Professor
        $professor = $this->person()->withName('Dr. Elena Vasquez')->withOccupation('Professor', 'University of Technology')->senior()->create();
        $persons[] = $professor;

        // PhD students
        $phdStudents = [];
        for ($i = 0; $i < 3; $i++) {
            $phd = $this->person()->withOccupation('PhD Student', 'University of Technology')->young()->create();
            $phdStudents[] = $phd;
            $persons[] = $phd;
            $relationships[] = $this->relationship()->between($professor['id'], $phd['id'])->mentorship()->create();
        }

        // Master's students
        for ($i = 0; $i < 5; $i++) {
            $masters = $this->person()->withOccupation('Masters Student', 'University of Technology')->young()->create();
            $persons[] = $masters;
            $relationships[] = $this->relationship()->between($professor['id'], $masters['id'])->academic()->create();
            
            // Some masters students work with PhD students
            if ($i < 3) {
                $relationships[] = $this->relationship()->between($phdStudents[$i]['id'], $masters['id'])->academic()->create();
            }
        }

        return ['persons' => $persons, 'relationships' => $relationships];
    }

    /**
     * Create a social circle
     */
    protected function createSocialCircle(): array
    {
        $persons = [];
        $relationships = [];

        // Create a group of friends
        for ($i = 0; $i < 6; $i++) {
            $person = $this->person()->young()->create();
            $persons[] = $person;
        }

        // Create friendships (everyone knows everyone)
        for ($i = 0; $i < count($persons); $i++) {
            for ($j = $i + 1; $j < count($persons); $j++) {
                $relationships[] = $this->relationship()->between($persons[$i]['id'], $persons[$j]['id'])->friendship()->create();
            }
        }

        return ['persons' => $persons, 'relationships' => $relationships];
    }

    /**
     * Get statistics about the generated data
     */
    public function getStats(): array
    {
        $personCount = $this->neo4j->runQuery('MATCH (p:Person) RETURN count(p) as count')->first()->get('count');
        $relationshipCount = $this->neo4j->runQuery('MATCH ()-[r]->() RETURN count(r) as count')->first()->get('count');
        
        $relationshipTypes = [];
        $typeResults = $this->neo4j->runQuery('MATCH ()-[r]->() RETURN type(r) as type, count(r) as count ORDER BY count DESC');
        foreach ($typeResults as $result) {
            $relationshipTypes[$result->get('type')] = $result->get('count');
        }

        return [
            'persons' => $personCount,
            'relationships' => $relationshipCount,
            'relationship_types' => $relationshipTypes,
        ];
    }
}
