<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Person;
use App\Models\Project;
use Illuminate\Console\Command;

class GraphModelDemo extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neo4j:graph-demo 
                            {--scenario=all : Scenario to run (all, basic, company, project, relationships)}
                            {--reset : Reset data before running demo}';

    /**
     * The console command description.
     */
    protected $description = 'Comprehensive demonstration of the Neo4j Graph Model system';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Neo4j Graph Model Demo');
        $this->newLine();

        if ($this->option('reset')) {
            $this->resetData();
        }

        $scenario = $this->option('scenario');

        switch ($scenario) {
            case 'all':
                $this->runFullDemo();
                break;
            case 'basic':
                $this->demonstrateBasicOperations();
                break;
            case 'company':
                $this->demonstrateCompanyOperations();
                break;
            case 'project':
                $this->demonstrateProjectOperations();
                break;
            case 'relationships':
                $this->demonstrateRelationships();
                break;
            default:
                $this->error("Unknown scenario: $scenario");

                return 1;
        }

        $this->newLine();
        $this->info('🎉 Demo completed successfully!');

        return 0;
    }

    /**
     * Run the complete demonstration
     */
    protected function runFullDemo(): void
    {
        $this->demonstrateBasicOperations();
        $this->demonstrateCompanyOperations();
        $this->demonstrateProjectOperations();
        $this->demonstrateRelationships();
        $this->demonstrateAdvancedQueries();
    }

    /**
     * Demonstrate basic CRUD operations
     */
    protected function demonstrateBasicOperations(): void
    {
        $this->info('📝 Basic Operations Demo');
        $this->line('================================');

        // Create some people
        $this->line('Creating people...');
        $alice = Person::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'age' => 32,
            'gender' => 'Female',
            'occupation' => 'Senior Developer',
            'company' => 'TechCorp',
            'department' => 'Engineering',
            'salary' => 95000,
            'skills' => ['PHP', 'JavaScript', 'React', 'MySQL'],
            'interests' => ['Technology', 'Reading', 'Hiking'],
            'active' => true,
        ]);

        $bob = Person::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'age' => 28,
            'gender' => 'Male',
            'occupation' => 'Product Manager',
            'company' => 'TechCorp',
            'department' => 'Product',
            'salary' => 85000,
            'skills' => ['Project Management', 'Agile', 'Scrum'],
            'interests' => ['Sports', 'Travel', 'Photography'],
            'active' => true,
        ]);

        $this->line("✅ Created: {$alice->name} (ID: {$alice->getId()})");
        $this->line("✅ Created: {$bob->name} (ID: {$bob->getId()})");

        // Demonstrate queries
        $this->line('📊 Query examples:');

        $allPeople = Person::all();
        $this->line("- Total people: {$allPeople->count()}");

        $adults = Person::adults()->get();
        $this->line("- Adults: {$adults->count()}");

        $techCorpEmployees = Person::inCompany('TechCorp')->get();
        $this->line("- TechCorp employees: {$techCorpEmployees->count()}");

        $developers = Person::withOccupation('Senior Developer')->get();
        $this->line("- Senior Developers: {$developers->count()}");

        // Update example
        $alice->setAttribute('salary', 100000);
        $alice->save();
        $this->line("✅ Updated Alice's salary to \${$alice->salary}");

        $this->newLine();
    }

    /**
     * Demonstrate company operations
     */
    protected function demonstrateCompanyOperations(): void
    {
        $this->info('🏢 Company Operations Demo');
        $this->line('=================================');

        // Create companies
        $techCorp = Company::create([
            'name' => 'TechCorp',
            'industry' => 'Technology',
            'size' => 150,
            'founded_year' => 2015,
            'headquarters' => 'San Francisco, CA',
            'website' => 'https://techcorp.com',
            'description' => 'Leading technology solutions provider',
            'revenue' => 50000000,
            'ceo' => 'Sarah Wilson',
            'active' => true,
        ]);

        $designStudio = Company::create([
            'name' => 'DesignStudio',
            'industry' => 'Design',
            'size' => 25,
            'founded_year' => 2018,
            'headquarters' => 'New York, NY',
            'website' => 'https://designstudio.com',
            'description' => 'Creative design agency',
            'revenue' => 5000000,
            'ceo' => 'Mike Chen',
            'active' => true,
        ]);

        $this->line("✅ Created: {$techCorp->name} ({$techCorp->getSizeCategory()})");
        $this->line("✅ Created: {$designStudio->name} ({$designStudio->getSizeCategory()})");

        // Add more employees to TechCorp
        $carol = Person::create([
            'name' => 'Carol Davis',
            'age' => 35,
            'occupation' => 'Engineering Manager',
            'company' => 'TechCorp',
            'department' => 'Engineering',
            'salary' => 120000,
            'active' => true,
        ]);

        // Get company statistics
        $stats = $techCorp->getStats();
        $this->line('📈 TechCorp Statistics:');
        foreach ($stats as $key => $value) {
            if (is_array($value)) {
                $this->line("  - $key: ".json_encode($value));
            } else {
                $this->line("  - $key: $value");
            }
        }

        // Demonstrate company queries
        $techCompanies = Company::inIndustry('Technology')->get();
        $this->line("🔍 Technology companies: {$techCompanies->count()}");

        $smallCompanies = Company::sizeRange(1, 50)->get();
        $this->line("🔍 Small companies (1-50 employees): {$smallCompanies->count()}");

        $this->newLine();
    }

    /**
     * Demonstrate project operations
     */
    protected function demonstrateProjectOperations(): void
    {
        $this->info('📋 Project Operations Demo');
        $this->line('===============================');

        // Create projects
        $mobileApp = Project::create([
            'name' => 'Mobile App Redesign',
            'description' => 'Complete redesign of our mobile application',
            'status' => 'In Progress',
            'priority' => 'High',
            'start_date' => '2024-01-01',
            'deadline' => '2024-06-30',
            'budget' => 250000,
            'actual_cost' => 180000,
            'progress' => 75,
            'technology_stack' => ['React Native', 'Node.js', 'MongoDB'],
            'active' => true,
        ]);

        $website = Project::create([
            'name' => 'Corporate Website',
            'description' => 'New corporate website with modern design',
            'status' => 'Planning',
            'priority' => 'Medium',
            'start_date' => '2024-03-01',
            'deadline' => '2024-08-15',
            'budget' => 75000,
            'actual_cost' => 15000,
            'progress' => 20,
            'technology_stack' => ['Laravel', 'Vue.js', 'MySQL'],
            'active' => true,
        ]);

        $this->line("✅ Created: {$mobileApp->name}");
        $this->line("✅ Created: {$website->name}");

        // Get people to assign to projects
        $alice = Person::where('name', '=', 'Alice Johnson')->first();
        $bob = Person::where('name', '=', 'Bob Smith')->first();
        $carol = Person::where('name', '=', 'Carol Davis')->first();

        if ($alice && $bob && $carol) {
            // Assign team members
            $mobileApp->assignPerson($alice, ['role' => 'Lead Developer', 'hours_per_week' => 40]);
            $mobileApp->assignPerson($bob, ['role' => 'Product Manager', 'hours_per_week' => 30]);
            $mobileApp->setManager($carol);

            $this->line("✅ Assigned team to {$mobileApp->name}");

            // Show project status
            $summary = $mobileApp->getSummary();
            $this->line("📊 {$mobileApp->name} Summary:");
            foreach ($summary as $key => $value) {
                if (is_array($value)) {
                    $this->line("  - $key: ".json_encode($value));
                } else {
                    $this->line("  - $key: $value");
                }
            }

            // Update progress
            $mobileApp->updateProgress(85);
            $this->line("✅ Updated {$mobileApp->name} progress to 85%");
        }

        // Project queries
        $activeProjects = Project::active()->get();
        $this->line("🔍 Active projects: {$activeProjects->count()}");

        $highPriorityProjects = Project::withPriority('High')->get();
        $this->line("🔍 High priority projects: {$highPriorityProjects->count()}");

        $this->newLine();
    }

    /**
     * Demonstrate relationship operations
     */
    protected function demonstrateRelationships(): void
    {
        $this->info('🔗 Relationship Operations Demo');
        $this->line('==================================');

        // Get people for relationship demo
        $people = Person::limit(3)->get();

        if ($people->count() >= 3) {
            $alice = $people[0];
            $bob = $people[1];
            $carol = $people[2];

            // Create relationships
            $alice->addFriend($bob);
            $alice->addColleague($carol, ['team' => 'Backend', 'since' => '2023-01-15']);
            $carol->manage($alice);

            $this->line("✅ Created friendship: {$alice->name} ↔ {$bob->name}");
            $this->line("✅ Created colleague relationship: {$alice->name} → {$carol->name}");
            $this->line("✅ Created management: {$carol->name} manages {$alice->name}");

            // Query relationships
            $aliceFriends = $alice->getFriends();
            $this->line("👥 {$alice->name}'s friends: {$aliceFriends->count()}");

            $aliceManager = $alice->getManager();
            $this->line("👨‍💼 {$alice->name}'s manager: ".($aliceManager ? $aliceManager->name : 'None'));

            $carolReports = $carol->getDirectReports();
            $this->line("👥 {$carol->name}'s direct reports: {$carolReports->count()}");

            // Company relationships
            $colleagues = $alice->getColleagues();
            $this->line("🏢 {$alice->name}'s colleagues: {$colleagues->count()}");

            $deptColleagues = $alice->getDepartmentColleagues();
            $this->line("🏢 {$alice->name}'s department colleagues: {$deptColleagues->count()}");
        }

        $this->newLine();
    }

    /**
     * Demonstrate advanced query features
     */
    protected function demonstrateAdvancedQueries(): void
    {
        $this->info('🎯 Advanced Query Demo');
        $this->line('=========================');

        // Complex queries
        $seniorTechPeople = Person::where('age', '>', 30)
            ->where('occupation', 'CONTAINS', 'Senior')
            ->orderByDesc('salary')
            ->limit(5)
            ->get();
        $this->line("🔍 Senior tech people (age > 30): {$seniorTechPeople->count()}");

        // Search functionality
        $searchResults = Person::search('Alice')->get();
        $this->line("🔍 Search results for 'Alice': {$searchResults->count()}");

        // Pagination example
        $page = Person::where('active', '=', true)->paginate(2, 1);
        $this->line("📄 Pagination: Page 1 shows {$page['data']->count()} of {$page['total']} total");

        // Aggregation examples
        $totalPeople = Person::count();
        $activeCount = Person::active()->count();
        $adultCount = Person::adults()->count();

        $this->line("📊 Counts - Total: $totalPeople, Active: $activeCount, Adults: $adultCount");

        // Company size analysis
        $companies = Company::all();
        if ($companies->count() > 0) {
            $this->line('📈 Company Analysis:');
            foreach ($companies as $company) {
                $this->line("  - {$company->name}: {$company->getSizeCategory()} ({$company->size} employees)");
            }
        }

        // Project status analysis
        $projects = Project::all();
        if ($projects->count() > 0) {
            $this->line('📋 Project Status:');
            foreach ($projects as $project) {
                $statusInfo = $project->getStatusInfo();
                $this->line("  - {$project->name}: {$statusInfo['progress']}% complete, ".
                          ($statusInfo['is_overdue'] ? 'OVERDUE' : 'On track'));
            }
        }

        $this->newLine();
    }

    /**
     * Reset all demo data
     */
    protected function resetData(): void
    {
        $this->warn('🧹 Resetting demo data...');

        try {
            // Delete test people
            $people = Person::where('name', 'CONTAINS', 'Demo')->get();
            foreach ($people as $person) {
                $person->delete();
            }

            // Delete specific demo people
            $demoNames = ['Alice Johnson', 'Bob Smith', 'Carol Davis'];
            foreach ($demoNames as $name) {
                $person = Person::where('name', '=', $name)->first();
                if ($person) {
                    $person->delete();
                }
            }

            // Delete demo companies
            $companies = Company::whereIn('name', ['TechCorp', 'DesignStudio'])->get();
            foreach ($companies as $company) {
                $company->delete();
            }

            // Delete demo projects
            $projects = Project::whereIn('name', ['Mobile App Redesign', 'Corporate Website'])->get();
            foreach ($projects as $project) {
                $project->delete();
            }

            $this->info('✅ Demo data reset complete');

        } catch (\Exception $e) {
            $this->error('❌ Error resetting data: '.$e->getMessage());
        }

        $this->newLine();
    }
}
