<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

class CreateCompanies extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'neo4j:create-companies 
                            {--count=10 : Number of companies to create}
                            {--industry= : Specific industry to focus on}
                            {--size= : Specific size range (small, medium, large, enterprise)}';

    /**
     * The console command description.
     */
    protected $description = 'Create sample companies in the Neo4j database';

    /**
     * Sample data for generating realistic companies
     */
    protected array $companyPrefixes = [
        'Tech', 'Global', 'Digital', 'Smart', 'Innovative', 'Advanced', 'Future', 'Next',
        'Blue', 'Red', 'Green', 'Alpha', 'Beta', 'Quantum', 'Cyber', 'Meta',
        'Cloud', 'Data', 'AI', 'Swift', 'Rapid', 'Dynamic', 'Elite', 'Prime',
    ];

    protected array $companySuffixes = [
        'Corp', 'Inc', 'LLC', 'Ltd', 'Technologies', 'Solutions', 'Systems',
        'Dynamics', 'Innovations', 'Enterprises', 'Group', 'Holdings',
        'Industries', 'Labs', 'Studios', 'Works', 'Partners', 'Ventures',
    ];

    protected array $industries = [
        'Technology', 'Finance', 'Healthcare', 'Manufacturing', 'Retail',
        'Education', 'Transportation', 'Real Estate', 'Energy', 'Media',
        'Telecommunications', 'Automotive', 'Aerospace', 'Biotechnology',
        'Consulting', 'Entertainment', 'Food & Beverage', 'Construction',
    ];

    protected array $cities = [
        'San Francisco', 'New York', 'London', 'Tokyo', 'Berlin',
        'Seattle', 'Austin', 'Boston', 'Chicago', 'Los Angeles',
        'Toronto', 'Sydney', 'Amsterdam', 'Singapore', 'Dublin',
    ];

    protected array $ceoNames = [
        'John Smith', 'Sarah Johnson', 'Michael Chen', 'Emily Davis',
        'Robert Wilson', 'Jennifer Brown', 'David Miller', 'Lisa Garcia',
        'Mark Anderson', 'Amanda Taylor', 'Chris Martinez', 'Rachel Thompson',
        'James White', 'Michelle Lee', 'Daniel Harris', 'Jessica Clark',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = (int) $this->option('count');
        $specificIndustry = $this->option('industry');
        $specificSize = $this->option('size');

        $this->info("🏢 Creating $count companies...");

        $created = 0;
        $failed = 0;

        for ($i = 1; $i <= $count; $i++) {
            try {
                $company = $this->generateCompany($specificIndustry, $specificSize);
                $companyModel = Company::create($company);

                $created++;
                $this->line("Created: {$companyModel->name} (ID: {$companyModel->getId()}) - {$companyModel->industry}");

            } catch (\Exception $e) {
                $failed++;
                $this->error("Failed to create company $i: ".$e->getMessage());
            }
        }

        $this->info("✅ Successfully created $created companies");
        if ($failed > 0) {
            $this->warn("⚠️  Failed to create $failed companies");
        }

        // Show summary statistics
        $this->showSummary();

        return 0;
    }

    /**
     * Generate a realistic company data array
     */
    protected function generateCompany(?string $specificIndustry = null, ?string $specificSize = null): array
    {
        $name = $this->generateCompanyName();
        $industry = $specificIndustry ?: $this->industries[array_rand($this->industries)];
        $foundedYear = rand(1950, 2023);
        $size = $this->generateCompanySize($specificSize);

        return [
            'name' => $name,
            'industry' => $industry,
            'size' => $size,
            'founded_year' => $foundedYear,
            'headquarters' => $this->cities[array_rand($this->cities)],
            'website' => 'https://'.strtolower(str_replace([' ', '&'], ['', 'and'], $name)).'.com',
            'description' => $this->generateDescription($industry),
            'revenue' => $this->generateRevenue($size),
            'stock_symbol' => $this->generateStockSymbol($name),
            'ceo' => $this->ceoNames[array_rand($this->ceoNames)],
            'active' => rand(1, 10) > 1, // 90% chance of being active
        ];
    }

    /**
     * Generate a realistic company name
     */
    protected function generateCompanyName(): string
    {
        $usePrefix = rand(1, 3) !== 1; // 66% chance of using prefix
        $useSuffix = rand(1, 2) === 1; // 50% chance of using suffix

        $name = '';

        if ($usePrefix) {
            $name .= $this->companyPrefixes[array_rand($this->companyPrefixes)];
        }

        // Sometimes add a descriptive word
        if (rand(1, 3) === 1) {
            $descriptors = ['Solutions', 'Systems', 'Digital', 'Global', 'Pro', 'Max', 'Plus'];
            $name .= ($name ? ' ' : '').$descriptors[array_rand($descriptors)];
        }

        if ($useSuffix) {
            $suffix = $this->companySuffixes[array_rand($this->companySuffixes)];
            $name .= ($name ? ' ' : '').$suffix;
        }

        // Fallback if name is empty
        if (empty($name)) {
            $name = $this->companyPrefixes[array_rand($this->companyPrefixes)].' '.
                   $this->companySuffixes[array_rand($this->companySuffixes)];
        }

        return $name;
    }

    /**
     * Generate company size based on specification
     */
    protected function generateCompanySize(?string $sizeCategory = null): int
    {
        return match ($sizeCategory) {
            'small' => rand(1, 49),
            'medium' => rand(50, 249),
            'large' => rand(250, 999),
            'enterprise' => rand(1000, 50000),
            default => match (rand(1, 4)) {
                1 => rand(1, 49),        // Small
                2 => rand(50, 249),      // Medium
                3 => rand(250, 999),     // Large
                4 => rand(1000, 50000),  // Enterprise
            }
        };
    }

    /**
     * Generate company description based on industry
     */
    protected function generateDescription(string $industry): string
    {
        $templates = [
            'Technology' => [
                'Leading provider of innovative software solutions',
                'Cutting-edge technology company specializing in digital transformation',
                'Advanced software development and consulting services',
                'Pioneer in cloud computing and AI technologies',
            ],
            'Finance' => [
                'Comprehensive financial services and investment solutions',
                'Leading financial institution with global reach',
                'Innovative fintech company revolutionizing banking',
                'Investment and wealth management specialists',
            ],
            'Healthcare' => [
                'Advanced healthcare solutions and medical technology',
                'Leading provider of pharmaceutical and biotechnology products',
                'Innovative medical devices and healthcare services',
                'Comprehensive healthcare management solutions',
            ],
            'Manufacturing' => [
                'Industrial manufacturing and production solutions',
                'Advanced manufacturing technologies and automation',
                'Quality manufacturing services and supply chain management',
                'Precision manufacturing and engineering services',
            ],
        ];

        $industryTemplates = $templates[$industry] ?? [
            "Leading company in the $industry sector",
            "Innovative solutions provider in $industry",
            "Professional services in $industry industry",
            "Market leader in $industry solutions",
        ];

        return $industryTemplates[array_rand($industryTemplates)];
    }

    /**
     * Generate revenue based on company size
     */
    protected function generateRevenue(int $size): float
    {
        // Rough correlation between size and revenue
        $baseRevenue = match (true) {
            $size < 10 => rand(100000, 1000000),           // $100K - $1M
            $size < 50 => rand(1000000, 10000000),         // $1M - $10M
            $size < 250 => rand(10000000, 100000000),      // $10M - $100M
            $size < 1000 => rand(100000000, 1000000000),   // $100M - $1B
            default => rand(1000000000, 50000000000),      // $1B - $50B
        };

        return $baseRevenue;
    }

    /**
     * Generate stock symbol from company name
     */
    protected function generateStockSymbol(string $name): string
    {
        // Extract first letters of words, max 4 characters
        $words = explode(' ', strtoupper($name));
        $symbol = '';

        foreach ($words as $word) {
            if (strlen($symbol) < 4 && ! empty($word)) {
                $symbol .= substr($word, 0, 1);
            }
        }

        // Pad with random letters if too short
        while (strlen($symbol) < 3) {
            $symbol .= chr(rand(65, 90)); // A-Z
        }

        return substr($symbol, 0, 4);
    }

    /**
     * Show summary of created companies
     */
    protected function showSummary(): void
    {
        $this->info('📊 Company Summary:');

        try {
            $totalCompanies = Company::count();
            $this->line("Total companies in database: $totalCompanies");

            // Industry breakdown
            $companies = Company::all();
            $industryBreakdown = $companies->groupBy('industry')->map->count();

            $this->line('');
            $this->info('Industries:');
            foreach ($industryBreakdown as $industry => $count) {
                $this->line("  - $industry: $count");
            }

            // Size categories
            $sizeCategories = $companies->groupBy(function ($company) {
                return $company->getSizeCategory();
            })->map->count();

            $this->line('');
            $this->info('Size Categories:');
            foreach ($sizeCategories as $category => $count) {
                $this->line("  - $category: $count");
            }

        } catch (\Exception $e) {
            $this->warn('Could not generate summary: '.$e->getMessage());
        }
    }
}
