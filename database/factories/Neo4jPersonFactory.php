<?php

namespace Database\Factories;

use App\Services\Neo4jService;
use Faker\Factory as FakerFactory;

class Neo4jPersonFactory
{
    protected $neo4j;

    protected $faker;

    protected $state = [];

    protected $count = 1;

    public function __construct(?Neo4jService $neo4j = null)
    {
        $this->neo4j = $neo4j ?: app(Neo4jService::class);
        $this->faker = FakerFactory::create();
    }

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'age' => $this->faker->numberBetween(18, 80),
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'occupation' => $this->faker->jobTitle(),
            'company' => $this->faker->company(),
            'bio' => $this->faker->sentence(10),
        ];
    }

    /**
     * Create a person node in Neo4j
     */
    public function create(array $attributes = []): array
    {
        $data = array_merge($this->definition(), $this->state, $attributes);

        $query = '
            CREATE (p:Person {
                name: $name,
                email: $email,
                age: $age,
                phone: $phone,
                address: $address,
                occupation: $occupation,
                company: $company,
                bio: $bio,
                created_at: datetime(),
                updated_at: datetime()
            })
            RETURN p
        ';

        $result = $this->neo4j->runQuery($query, $data);
        $person = $result->first()->get('p');

        return [
            'id' => $person->getId(),
            'properties' => $person->getProperties(),
        ];
    }

    /**
     * Set state for the factory
     */
    public function state(array $state): self
    {
        $this->state = array_merge($this->state, $state);

        return $this;
    }

    /**
     * Create a person with specific occupation
     */
    public function withOccupation(string $occupation, ?string $company = null): self
    {
        return $this->state([
            'occupation' => $occupation,
            'company' => $company ?? $this->faker->company(),
        ]);
    }

    /**
     * Create a young person (18-30)
     */
    public function young(): self
    {
        return $this->state([
            'age' => $this->faker->numberBetween(18, 30),
        ]);
    }

    /**
     * Create a middle-aged person (31-50)
     */
    public function middleAged(): self
    {
        return $this->state([
            'age' => $this->faker->numberBetween(31, 50),
        ]);
    }

    /**
     * Create a senior person (51-80)
     */
    public function senior(): self
    {
        return $this->state([
            'age' => $this->faker->numberBetween(51, 80),
        ]);
    }

    /**
     * Create a person with a specific name
     */
    public function withName(string $name): self
    {
        return $this->state(['name' => $name]);
    }

    /**
     * Create a person with a specific email
     */
    public function withEmail(string $email): self
    {
        return $this->state(['email' => $email]);
    }

    /**
     * Create a tech worker
     */
    public function techWorker(): self
    {
        $techJobs = [
            'Software Engineer',
            'Data Scientist',
            'DevOps Engineer',
            'Product Manager',
            'UX Designer',
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'Systems Administrator',
            'Security Engineer',
        ];

        $techCompanies = [
            'TechCorp Inc.',
            'DataFlow Solutions',
            'CloudNine Systems',
            'InnovateTech',
            'DigitalForge',
            'CodeCrafters',
            'ByteBuilders',
            'WebWorks Ltd.',
            'AI Innovations',
            'CyberSolutions',
        ];

        return $this->state([
            'occupation' => $this->faker->randomElement($techJobs),
            'company' => $this->faker->randomElement($techCompanies),
        ]);
    }

    /**
     * Create a business professional
     */
    public function businessPro(): self
    {
        $businessJobs = [
            'Marketing Manager',
            'Sales Director',
            'Business Analyst',
            'Project Manager',
            'Operations Manager',
            'Financial Analyst',
            'HR Manager',
            'Account Executive',
            'Business Development Manager',
            'Strategy Consultant',
        ];

        return $this->state([
            'occupation' => $this->faker->randomElement($businessJobs),
        ]);
    }
}
