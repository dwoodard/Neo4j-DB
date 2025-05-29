<?php

namespace App\Services;

use Database\Factories\Neo4jPersonFactory;
use Database\Factories\Neo4jRelationshipFactory;
use Database\Factories\AdvancedNeo4jPersonFactory;

class Neo4jFactoryService
{
    protected $neo4j;
    protected $personFactory;
    protected $relationshipFactory;
    protected $advancedPersonFactory;

    public function __construct(Neo4jService $neo4j)
    {
        $this->neo4j = $neo4j;
        $this->personFactory = new Neo4jPersonFactory(neo4j: $neo4j);
        $this->relationshipFactory = new Neo4jRelationshipFactory(neo4j: $neo4j);
        $this->advancedPersonFactory = new AdvancedNeo4jPersonFactory(neo4j: $neo4j);
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
            case 'startup_ecosystem':
                return $this->createStartupEcosystem();
            case 'university_campus':
                return $this->createUniversityCampus();
            case 'sports_league':
                return $this->createSportsLeague();
            case 'multinational_corp':
                return $this->createMultinationalCorp();
            case 'creative_agency':
                return $this->createCreativeAgency();
            case 'research_institute':
                return $this->createResearchInstitute();
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
     * Create a startup ecosystem scenario
     */
    protected function createStartupEcosystem(): array
    {
        $persons = [];
        $relationships = [];

        // Founders
        $founder1 = $this->advancedPersonFactory->industry('tech')->withName('Alex Thompson')->withOccupation('CEO & Founder')->create();
        $founder2 = $this->advancedPersonFactory->industry('tech')->withName('Maria Rodriguez')->withOccupation('CTO & Co-Founder')->create();
        $persons = array_merge($persons, [$founder1, $founder2]);

        // Investors
        $investor1 = $this->advancedPersonFactory->industry('finance')->withName('Robert Chen')->withOccupation('Venture Capitalist')->create();
        $investor2 = $this->advancedPersonFactory->industry('finance')->withName('Sarah Kim')->withOccupation('Angel Investor')->create();
        $persons = array_merge($persons, [$investor1, $investor2]);

        // Development team
        for ($i = 0; $i < 8; $i++) {
            $developer = $this->advancedPersonFactory->industry('tech')->demographic('millennials')->create();
            $persons[] = $developer;
            
            // Developers work with founders
            $relationships[] = $this->relationshipFactory->workRelationship($developer['id'], $founder1['id']);
            $relationships[] = $this->relationshipFactory->workRelationship($developer['id'], $founder2['id']);
        }

        // Advisors
        for ($i = 0; $i < 3; $i++) {
            $advisor = $this->advancedPersonFactory->industry('tech')->demographic('gen_x')->withOccupation('Startup Advisor')->create();
            $persons[] = $advisor;
            
            // Advisors mentor founders
            $relationships[] = $this->relationshipFactory->mentorship($advisor['id'], $founder1['id']);
            $relationships[] = $this->relationshipFactory->mentorship($advisor['id'], $founder2['id']);
        }

        // Founder relationships
        $relationships[] = $this->relationshipFactory->workRelationship($founder1['id'], $founder2['id']);
        
        // Investor relationships
        $relationships[] = $this->relationshipFactory->workRelationship($investor1['id'], $founder1['id']);
        $relationships[] = $this->relationshipFactory->workRelationship($investor2['id'], $founder1['id']);

        return [
            'persons' => $persons,
            'relationships' => $relationships,
        ];
    }

    /**
     * Create a university campus scenario
     */
    protected function createUniversityCampus(): array
    {
        $persons = [];
        $relationships = [];

        // Faculty
        $dean = $this->advancedPersonFactory->industry('education')->withName('Dr. Patricia Williams')->withOccupation('Dean')->create();
        $persons[] = $dean;

        // Professors
        for ($i = 0; $i < 6; $i++) {
            $professor = $this->advancedPersonFactory->industry('education')->demographic('gen_x')->withOccupation('Professor')->create();
            $persons[] = $professor;
            
            // Professors report to dean
            $relationships[] = $this->relationshipFactory->workRelationship($professor['id'], $dean['id']);
        }

        // Graduate students
        for ($i = 0; $i < 12; $i++) {
            $gradStudent = $this->advancedPersonFactory->industry('education')->demographic('millennials')->withOccupation('Graduate Student')->create();
            $persons[] = $gradStudent;
            
            // Random professor mentorship
            $professorId = $persons[rand(1, 6)]['id'];
            $relationships[] = $this->relationshipFactory->academic($professorId, $gradStudent['id']);
        }

        // Undergraduate students
        for ($i = 0; $i < 20; $i++) {
            $undergrad = $this->advancedPersonFactory->industry('education')->demographic('gen_z')->withOccupation('Undergraduate Student')->create();
            $persons[] = $undergrad;
            
            // Random professor relationships
            if (rand(1, 3) === 1) {
                $professorId = $persons[rand(1, 6)]['id'];
                $relationships[] = $this->relationshipFactory->academic($professorId, $undergrad['id']);
            }
        }

        // Student friendships
        $studentStart = 7; // After dean and professors
        for ($i = 0; $i < 15; $i++) {
            $student1 = rand($studentStart, count($persons) - 1);
            $student2 = rand($studentStart, count($persons) - 1);
            
            if ($student1 !== $student2) {
                $relationships[] = $this->relationshipFactory->friendship($persons[$student1]['id'], $persons[$student2]['id']);
            }
        }

        return [
            'persons' => $persons,
            'relationships' => $relationships,
        ];
    }

    /**
     * Create a sports league scenario
     */
    protected function createSportsLeague(): array
    {
        $persons = [];
        $relationships = [];

        // League commissioner
        $commissioner = $this->advancedPersonFactory->withName('James Miller')->withOccupation('League Commissioner')->create();
        $persons[] = $commissioner;

        // Create 3 teams
        for ($team = 1; $team <= 3; $team++) {
            // Team coach
            $coach = $this->advancedPersonFactory->demographic('gen_x')->withOccupation('Head Coach')->create();
            $persons[] = $coach;
            
            // Coach reports to commissioner
            $relationships[] = $this->relationshipFactory->workRelationship($coach['id'], $commissioner['id']);

            // Team captain
            $captain = $this->advancedPersonFactory->demographic('millennials')->withOccupation('Team Captain')->create();
            $persons[] = $captain;
            
            // Captain works with coach
            $relationships[] = $this->relationshipFactory->workRelationship($captain['id'], $coach['id']);

            // Team players
            for ($i = 0; $i < 8; $i++) {
                $player = $this->advancedPersonFactory->demographic('millennials')->withOccupation('Professional Athlete')->create();
                $persons[] = $player;
                
                // Players work with coach and captain
                $relationships[] = $this->relationshipFactory->workRelationship($player['id'], $coach['id']);
                $relationships[] = $this->relationshipFactory->workRelationship($player['id'], $captain['id']);
                
                // Team friendships
                if (rand(1, 3) === 1) {
                    $relationships[] = $this->relationshipFactory->friendship($player['id'], $captain['id']);
                }
            }
        }

        return [
            'persons' => $persons,
            'relationships' => $relationships,
        ];
    }

    /**
     * Create a multinational corporation scenario
     */
    protected function createMultinationalCorp(): array
    {
        $persons = [];
        $relationships = [];

        // Global CEO
        $ceo = $this->advancedPersonFactory->withName('Elizabeth Carter')->withOccupation('Global CEO')->create();
        $persons[] = $ceo;

        // Regional VPs
        $regions = ['Americas', 'Europe', 'Asia-Pacific'];
        $regionalVPs = [];
        
        foreach ($regions as $region) {
            $vp = $this->advancedPersonFactory->demographic('gen_x')->withOccupation("VP {$region}")->create();
            $persons[] = $vp;
            $regionalVPs[] = $vp;
            
            // VPs report to CEO
            $relationships[] = $this->relationshipFactory->management($ceo['id'], $vp['id']);
        }

        // Regional teams
        foreach ($regionalVPs as $vp) {
            // Regional directors
            for ($i = 0; $i < 3; $i++) {
                $director = $this->advancedPersonFactory->demographic('millennials')->withOccupation('Regional Director')->create();
                $persons[] = $director;
                
                // Directors report to VP
                $relationships[] = $this->relationshipFactory->management($vp['id'], $director['id']);
                
                // Department managers
                for ($j = 0; $j < 2; $j++) {
                    $manager = $this->advancedPersonFactory->demographic('millennials')->withOccupation('Manager')->create();
                    $persons[] = $manager;
                    
                    // Managers report to director
                    $relationships[] = $this->relationshipFactory->management($director['id'], $manager['id']);
                    
                    // Team members
                    for ($k = 0; $k < 4; $k++) {
                        $employee = $this->advancedPersonFactory->demographic('millennials')->create();
                        $persons[] = $employee;
                        
                        // Employees report to manager
                        $relationships[] = $this->relationshipFactory->management($manager['id'], $employee['id']);
                    }
                }
            }
        }

        // Cross-regional collaborations
        for ($i = 0; $i < 10; $i++) {
            $person1 = rand(1, count($persons) - 1);
            $person2 = rand(1, count($persons) - 1);
            
            if ($person1 !== $person2 && rand(1, 4) === 1) {
                $relationships[] = $this->relationshipFactory->workRelationship($persons[$person1]['id'], $persons[$person2]['id']);
            }
        }

        return [
            'persons' => $persons,
            'relationships' => $relationships,
        ];
    }

    /**
     * Create a creative agency scenario
     */
    protected function createCreativeAgency(): array
    {
        $persons = [];
        $relationships = [];

        // Agency founder/creative director
        $creativeDirector = $this->advancedPersonFactory->withName('Isabella Martinez')->withOccupation('Creative Director')->create();
        $persons[] = $creativeDirector;

        // Department heads
        $departments = ['Art Direction', 'Copywriting', 'Strategy', 'Account Management', 'Production'];
        $heads = [];
        
        foreach ($departments as $dept) {
            $head = $this->advancedPersonFactory->demographic('gen_x')->withOccupation("{$dept} Head")->create();
            $persons[] = $head;
            $heads[] = $head;
            
            // Heads report to creative director
            $relationships[] = $this->relationshipFactory->management($creativeDirector['id'], $head['id']);
        }

        // Team members for each department
        foreach ($heads as $head) {
            for ($i = 0; $i < rand(3, 6); $i++) {
                $teamMember = $this->advancedPersonFactory->demographic('millennials')->create();
                $persons[] = $teamMember;
                
                // Team members report to head
                $relationships[] = $this->relationshipFactory->management($head['id'], $teamMember['id']);
                
                // Creative collaboration relationships
                if (rand(1, 3) === 1) {
                    $relationships[] = $this->relationshipFactory->workRelationship($teamMember['id'], $creativeDirector['id']);
                }
            }
        }

        // Freelancers
        for ($i = 0; $i < 5; $i++) {
            $freelancer = $this->advancedPersonFactory->demographic('millennials')->withOccupation('Freelancer')->create();
            $persons[] = $freelancer;
            
            // Freelancers work with random team members
            $randomPerson = rand(1, count($persons) - 6);
            $relationships[] = $this->relationshipFactory->workRelationship($freelancer['id'], $persons[$randomPerson]['id']);
        }

        return [
            'persons' => $persons,
            'relationships' => $relationships,
        ];
    }

    /**
     * Create a research institute scenario
     */
    protected function createResearchInstitute(): array
    {
        $persons = [];
        $relationships = [];

        // Institute director
        $director = $this->advancedPersonFactory->industry('education')->withName('Dr. Richard Thompson')->withOccupation('Institute Director')->create();
        $persons[] = $director;

        // Research group leaders
        for ($i = 0; $i < 4; $i++) {
            $leader = $this->advancedPersonFactory->industry('education')->demographic('gen_x')->withOccupation('Research Group Leader')->create();
            $persons[] = $leader;
            
            // Leaders report to director
            $relationships[] = $this->relationshipFactory->management($director['id'], $leader['id']);
            
            // Senior researchers
            for ($j = 0; $j < 3; $j++) {
                $senior = $this->advancedPersonFactory->industry('education')->demographic('millennials')->withOccupation('Senior Researcher')->create();
                $persons[] = $senior;
                
                // Senior researchers work with leader
                $relationships[] = $this->relationshipFactory->academic($leader['id'], $senior['id']);
                
                // Junior researchers
                for ($k = 0; $k < 2; $k++) {
                    $junior = $this->advancedPersonFactory->industry('education')->demographic('gen_z')->withOccupation('Junior Researcher')->create();
                    $persons[] = $junior;
                    
                    // Junior researchers work with senior
                    $relationships[] = $this->relationshipFactory->mentorship($senior['id'], $junior['id']);
                }
            }
        }

        // Visiting scholars
        for ($i = 0; $i < 3; $i++) {
            $visitor = $this->advancedPersonFactory->industry('education')->withOccupation('Visiting Scholar')->create();
            $persons[] = $visitor;
            
            // Visiting scholars collaborate with random researchers
            $randomResearcher = rand(1, count($persons) - 4);
            $relationships[] = $this->relationshipFactory->academic($visitor['id'], $persons[$randomResearcher]['id']);
        }

        return [
            'persons' => $persons,
            'relationships' => $relationships,
        ];
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
