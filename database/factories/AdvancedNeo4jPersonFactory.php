<?php

namespace Database\Factories;

use App\Services\Neo4jService;
use Faker\Factory as FakerFactory;

class AdvancedNeo4jPersonFactory
{
    protected $neo4j;

    protected $faker;

    protected $state = [];

    protected $count = 1;

    protected $validationRules = [];

    public function __construct(?Neo4jService $neo4j = null)
    {
        $this->neo4j = $neo4j ?: app(Neo4jService::class);
        $this->faker = FakerFactory::create();
        $this->setupValidationRules();
    }

    /**
     * Setup validation rules for generated data
     */
    protected function setupValidationRules(): void
    {
        $this->validationRules = [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'age' => ['required', 'integer', 'min:0', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'company' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Define the model's default state with enhanced data quality
     */
    public function definition(): array
    {
        $gender = $this->faker->randomElement(['male', 'female']);
        $age = $this->faker->numberBetween(18, 80);

        return [
            'name' => $this->faker->name($gender),
            'email' => $this->faker->unique()->safeEmail(),
            'age' => $age,
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'occupation' => $this->getOccupationByAge($age),
            'company' => $this->faker->company(),
            'bio' => $this->generateRealisticBio($age),
            'gender' => $gender,
            'nationality' => $this->faker->countryCode(),
            'education_level' => $this->getEducationByAge($age),
            'salary_range' => $this->getSalaryByOccupation(),
            'interests' => $this->generateInterests(),
            'skills' => $this->generateSkills(),
            'social_media' => $this->generateSocialMedia(),
        ];
    }

    /**
     * Get realistic occupation based on age
     */
    protected function getOccupationByAge(int $age): string
    {
        if ($age < 25) {
            return $this->faker->randomElement([
                'Student', 'Intern', 'Junior Developer', 'Sales Associate',
                'Customer Service Rep', 'Barista', 'Retail Worker',
            ]);
        } elseif ($age < 35) {
            return $this->faker->randomElement([
                'Software Engineer', 'Marketing Specialist', 'Accountant',
                'Teacher', 'Nurse', 'Graphic Designer', 'Project Manager',
            ]);
        } elseif ($age < 50) {
            return $this->faker->randomElement([
                'Senior Developer', 'Marketing Manager', 'Operations Manager',
                'Principal', 'Senior Nurse', 'Creative Director', 'Director',
            ]);
        } else {
            return $this->faker->randomElement([
                'CTO', 'VP Marketing', 'CEO', 'Consultant', 'Professor',
                'Chief Nurse', 'Executive Director', 'Retired',
            ]);
        }
    }

    /**
     * Get education level based on age
     */
    protected function getEducationByAge(int $age): string
    {
        if ($age < 22) {
            return $this->faker->randomElement(['High School', 'Some College', 'Associate Degree']);
        } elseif ($age < 30) {
            return $this->faker->randomElement(['Bachelor\'s Degree', 'Some College', 'Master\'s Degree']);
        } else {
            return $this->faker->randomElement(['Bachelor\'s Degree', 'Master\'s Degree', 'PhD', 'Professional Degree']);
        }
    }

    /**
     * Get salary range based on occupation
     */
    protected function getSalaryByOccupation(): string
    {
        $occupation = $this->state['occupation'] ?? 'Unknown';

        if (str_contains(strtolower($occupation), 'ceo') || str_contains(strtolower($occupation), 'vp')) {
            return '$150,000 - $300,000+';
        } elseif (str_contains(strtolower($occupation), 'senior') || str_contains(strtolower($occupation), 'manager')) {
            return '$80,000 - $150,000';
        } elseif (str_contains(strtolower($occupation), 'engineer') || str_contains(strtolower($occupation), 'developer')) {
            return '$60,000 - $120,000';
        } elseif (str_contains(strtolower($occupation), 'student') || str_contains(strtolower($occupation), 'intern')) {
            return '$0 - $30,000';
        } else {
            return '$35,000 - $75,000';
        }
    }

    /**
     * Generate realistic bio based on age and occupation
     */
    protected function generateRealisticBio(int $age): string
    {
        $templates = [
            'Passionate professional with {years} years of experience in {field}. Enjoys {hobby} and {activity}.',
            'Creative individual focused on {field}. Has been working in the industry for {years} years. Loves {hobby}.',
            'Experienced {role} with a background in {field}. Passionate about {interest} and {activity}.',
        ];

        return str_replace([
            '{years}', '{field}', '{hobby}', '{activity}', '{role}', '{interest}',
        ], [
            max(1, $age - 22),
            $this->faker->randomElement(['technology', 'business', 'education', 'healthcare', 'finance']),
            $this->faker->randomElement(['reading', 'hiking', 'cooking', 'photography', 'music']),
            $this->faker->randomElement(['traveling', 'learning', 'volunteering', 'sports', 'art']),
            $this->faker->randomElement(['professional', 'leader', 'innovator', 'creator', 'mentor']),
            $this->faker->randomElement(['innovation', 'teamwork', 'growth', 'excellence', 'creativity']),
        ], $this->faker->randomElement($templates));
    }

    /**
     * Generate interests array
     */
    protected function generateInterests(): array
    {
        $allInterests = [
            'Technology', 'Sports', 'Music', 'Art', 'Travel', 'Cooking', 'Reading',
            'Photography', 'Gaming', 'Fitness', 'Movies', 'Nature', 'Fashion',
            'Science', 'History', 'Politics', 'Business', 'Entrepreneurship',
        ];

        return $this->faker->randomElements($allInterests, $this->faker->numberBetween(2, 6));
    }

    /**
     * Generate skills array
     */
    protected function generateSkills(): array
    {
        $technicalSkills = [
            'JavaScript', 'Python', 'Java', 'PHP', 'React', 'Vue.js', 'Node.js',
            'SQL', 'MongoDB', 'AWS', 'Docker', 'Git', 'API Development',
        ];

        $softSkills = [
            'Leadership', 'Communication', 'Problem Solving', 'Teamwork',
            'Project Management', 'Time Management', 'Critical Thinking',
        ];

        $skills = array_merge(
            $this->faker->randomElements($technicalSkills, $this->faker->numberBetween(2, 5)),
            $this->faker->randomElements($softSkills, $this->faker->numberBetween(2, 4))
        );

        return array_slice($skills, 0, $this->faker->numberBetween(4, 8));
    }

    /**
     * Generate social media profiles
     */
    protected function generateSocialMedia(): array
    {
        $platforms = ['LinkedIn', 'Twitter', 'GitHub', 'Instagram', 'Facebook'];
        $selectedPlatforms = $this->faker->randomElements($platforms, $this->faker->numberBetween(1, 3));

        $profiles = [];
        foreach ($selectedPlatforms as $platform) {
            $profiles[strtolower($platform)] = $this->faker->url();
        }

        return $profiles;
    }

    /**
     * Validate generated data against rules
     */
    protected function validate(array $data): bool
    {
        foreach ($this->validationRules as $field => $rules) {
            $value = $data[$field] ?? null;

            foreach ($rules as $rule) {
                if ($rule === 'required' && empty($value)) {
                    throw new \InvalidArgumentException("Field {$field} is required");
                }

                if ($rule === 'email' && $value && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Field {$field} must be a valid email");
                }

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (is_string($value) && strlen($value) < $min) {
                        throw new \InvalidArgumentException("Field {$field} must be at least {$min} characters");
                    }
                    if (is_numeric($value) && $value < $min) {
                        throw new \InvalidArgumentException("Field {$field} must be at least {$min}");
                    }
                }

                if (str_starts_with($rule, 'max:')) {
                    $max = (int) substr($rule, 4);
                    if (is_string($value) && strlen($value) > $max) {
                        throw new \InvalidArgumentException("Field {$field} must not exceed {$max} characters");
                    }
                    if (is_numeric($value) && $value > $max) {
                        throw new \InvalidArgumentException("Field {$field} must not exceed {$max}");
                    }
                }
            }
        }

        return true;
    }

    /**
     * Create a person node with validation
     */
    public function create(array $attributes = []): array
    {
        $data = array_merge($this->definition(), $this->state, $attributes);

        // Validate data
        $this->validate($data);

        // Convert arrays to JSON strings for Neo4j storage
        $data['interests'] = json_encode($data['interests']);
        $data['skills'] = json_encode($data['skills']);
        $data['social_media'] = json_encode($data['social_media']);

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
                gender: $gender,
                nationality: $nationality,
                education_level: $education_level,
                salary_range: $salary_range,
                interests: $interests,
                skills: $skills,
                social_media: $social_media,
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
     * Create multiple persons
     */
    public function count(int $count): self
    {
        $this->count = $count;

        return $this;
    }

    /**
     * Create with specific demographics
     */
    public function demographic(string $type): self
    {
        switch ($type) {
            case 'millennials':
                return $this->state(['age' => $this->faker->numberBetween(28, 42)]);
            case 'gen_z':
                return $this->state(['age' => $this->faker->numberBetween(18, 27)]);
            case 'gen_x':
                return $this->state(['age' => $this->faker->numberBetween(43, 58)]);
            case 'boomers':
                return $this->state(['age' => $this->faker->numberBetween(59, 77)]);
            default:
                return $this;
        }
    }

    /**
     * Create with specific industry focus
     */
    public function industry(string $industry): self
    {
        $occupations = [
            'tech' => [
                'Software Engineer', 'Data Scientist', 'Product Manager', 'DevOps Engineer',
                'UX Designer', 'Systems Architect', 'QA Engineer', 'Technical Writer',
            ],
            'finance' => [
                'Financial Analyst', 'Investment Banker', 'Accountant', 'Risk Manager',
                'Portfolio Manager', 'Financial Advisor', 'Auditor', 'Compliance Officer',
            ],
            'healthcare' => [
                'Doctor', 'Nurse', 'Pharmacist', 'Physical Therapist',
                'Medical Technician', 'Healthcare Administrator', 'Surgeon', 'Therapist',
            ],
            'education' => [
                'Teacher', 'Professor', 'Principal', 'Librarian',
                'Education Administrator', 'Curriculum Developer', 'Tutor', 'Researcher',
            ],
        ];

        if (isset($occupations[$industry])) {
            return $this->state([
                'occupation' => $this->faker->randomElement($occupations[$industry]),
                'company' => $this->generateCompanyForIndustry($industry),
            ]);
        }

        return $this;
    }

    /**
     * Generate company name for specific industry
     */
    protected function generateCompanyForIndustry(string $industry): string
    {
        $suffixes = [
            'tech' => ['Tech', 'Systems', 'Solutions', 'Labs', 'Digital', 'Software'],
            'finance' => ['Financial', 'Capital', 'Investments', 'Bank', 'Securities', 'Group'],
            'healthcare' => ['Medical Center', 'Hospital', 'Health Services', 'Clinic', 'Care', 'Medical Group'],
            'education' => ['University', 'College', 'Academy', 'Institute', 'School', 'Learning Center'],
        ];

        $prefix = $this->faker->company();
        $suffix = $this->faker->randomElement($suffixes[$industry] ?? ['Corp']);

        return $prefix.' '.$suffix;
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
     * Magic method for fluent interface
     */
    public function __call(string $method, array $arguments): self
    {
        // Handle withX methods
        if (str_starts_with($method, 'with')) {
            $property = strtolower(substr($method, 4));
            $value = $arguments[0] ?? null;

            return $this->state([$property => $value]);
        }

        // Handle demographic shortcuts
        $demographics = ['young', 'middleAged', 'senior', 'millennial', 'genZ', 'genX', 'boomer'];
        if (in_array($method, $demographics)) {
            return $this->demographic(snake_case($method));
        }

        throw new \BadMethodCallException("Method {$method} does not exist");
    }
}
