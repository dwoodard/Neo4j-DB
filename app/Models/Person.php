<?php

namespace App\Models;

use App\Graph\GraphModel;
use Illuminate\Support\Collection;

class Person extends GraphModel
{
    protected static string $label = 'Person';

    /**
     * Attributes that should be cast to specific types
     */
    protected array $casts = [
        'age' => 'integer',
        'salary' => 'float',
        'active' => 'boolean',
        'skills' => 'array',
        'interests' => 'array',
    ];

    /**
     * Attributes that are mass assignable
     */
    protected array $fillable = [
        'name',
        'email',
        'age',
        'gender',
        'occupation',
        'company',
        'department',
        'salary',
        'education_level',
        'skills',
        'interests',
        'bio',
        'phone',
        'address',
        'city',
        'country',
        'social_media',
        'active',
    ];

    /**
     * Get the person's full name
     */
    public function getFullName(): string
    {
        return $this->getAttribute('name') ?? 'Unknown';
    }

    /**
     * Get the person's age group
     */
    public function getAgeGroup(): string
    {
        $age = $this->getAttribute('age');

        if (! $age) {
            return 'Unknown';
        }

        return match (true) {
            $age < 18 => 'Minor',
            $age < 25 => 'Young Adult',
            $age < 35 => 'Adult',
            $age < 50 => 'Middle Age',
            $age < 65 => 'Mature',
            default => 'Senior'
        };
    }

    /**
     * Check if person is an adult
     */
    public function isAdult(): bool
    {
        return ($this->getAttribute('age') ?? 0) >= 18;
    }

    /**
     * Get colleagues (people in the same company)
     */
    public function getColleagues(): Collection
    {
        $company = $this->getAttribute('company');

        if (! $company) {
            return collect();
        }

        return static::where('company', '=', $company)
            ->where('id', '!=', $this->getId())
            ->get();
    }

    /**
     * Get people in the same department
     */
    public function getDepartmentColleagues(): Collection
    {
        $company = $this->getAttribute('company');
        $department = $this->getAttribute('department');

        if (! $company || ! $department) {
            return collect();
        }

        return static::where('company', '=', $company)
            ->where('department', '=', $department)
            ->where('id', '!=', $this->getId())
            ->get();
    }

    /**
     * Get people with similar skills
     */
    public function getPeopleWithSimilarSkills(): Collection
    {
        $skills = $this->getAttribute('skills');

        if (! is_array($skills) || empty($skills)) {
            return collect();
        }

        // This is a simplified version - in real Neo4j we'd use array operations
        $people = collect();

        foreach ($skills as $skill) {
            $skillMatches = static::where('skills', 'CONTAINS', $skill)
                ->where('id', '!=', $this->getId())
                ->get();

            $people = $people->merge($skillMatches);
        }

        return $people->unique('id');
    }

    /**
     * Get friends (people connected via FRIEND relationship)
     */
    public function getFriends(): Collection
    {
        return $this->getRelated('FRIEND', 'both');
    }

    /**
     * Get direct reports (people this person manages)
     */
    public function getDirectReports(): Collection
    {
        return $this->getRelated('MANAGES', 'out');
    }

    /**
     * Get manager (person who manages this person)
     */
    public function getManager(): ?Person
    {
        $managers = $this->getRelated('MANAGES', 'in');

        return $managers->first();
    }

    /**
     * Add a friend relationship
     */
    public function addFriend(Person $person): bool
    {
        return $this->createRelationshipTo($person, 'FRIEND');
    }

    /**
     * Add a colleague relationship
     */
    public function addColleague(Person $person, array $properties = []): bool
    {
        return $this->createRelationshipTo($person, 'COLLEAGUE', $properties);
    }

    /**
     * Set this person as manager of another person
     */
    public function manage(Person $person): bool
    {
        return $this->createRelationshipTo($person, 'MANAGES');
    }

    /**
     * Scope for finding adults
     */
    public static function adults(): \App\Graph\GraphQueryBuilder
    {
        return static::where('age', '>=', 18);
    }

    /**
     * Scope for finding people by company
     */
    public static function inCompany(string $company): \App\Graph\GraphQueryBuilder
    {
        return static::where('company', '=', $company);
    }

    /**
     * Scope for finding people by occupation
     */
    public static function withOccupation(string $occupation): \App\Graph\GraphQueryBuilder
    {
        return static::where('occupation', '=', $occupation);
    }

    /**
     * Scope for finding people in age range
     */
    public static function ageRange(int $min, int $max): \App\Graph\GraphQueryBuilder
    {
        return static::where('age', '>=', $min)->where('age', '<=', $max);
    }

    /**
     * Scope for finding active people
     */
    public static function active(): \App\Graph\GraphQueryBuilder
    {
        return static::where('active', '=', true);
    }

    /**
     * Search people by name or email
     */
    public static function search(string $term): \App\Graph\GraphQueryBuilder
    {
        return (new \App\Graph\GraphQueryBuilder(static::class))
            ->search($term, ['name', 'email']);
    }

    /**
     * Cast attributes to appropriate types
     */
    public function getAttribute(string $key): mixed
    {
        $value = parent::getAttribute($key);

        if (isset($this->casts[$key]) && $value !== null) {
            return match ($this->casts[$key]) {
                'integer' => (int) $value,
                'float' => (float) $value,
                'boolean' => (bool) $value,
                'array' => is_array($value) ? $value : json_decode($value, true),
                default => $value
            };
        }

        return $value;
    }

    /**
     * Set an attribute with casting
     */
    public function setAttribute(string $key, mixed $value): void
    {
        if (isset($this->casts[$key])) {
            $value = match ($this->casts[$key]) {
                'array' => is_array($value) ? json_encode($value) : $value,
                default => $value
            };
        }

        parent::setAttribute($key, $value);
    }

    /**
     * Get a summary of the person
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getFullName(),
            'age' => $this->getAttribute('age'),
            'age_group' => $this->getAgeGroup(),
            'occupation' => $this->getAttribute('occupation'),
            'company' => $this->getAttribute('company'),
            'department' => $this->getAttribute('department'),
            'is_adult' => $this->isAdult(),
            'email' => $this->getAttribute('email'),
            'skills_count' => count($this->getAttribute('skills') ?? []),
            'interests_count' => count($this->getAttribute('interests') ?? []),
        ];
    }

    /**
     * Convert to array with formatted data
     */
    public function toArray(): array
    {
        $data = parent::toArray();

        // Ensure arrays are properly formatted
        if (isset($data['skills']) && is_string($data['skills'])) {
            $data['skills'] = json_decode($data['skills'], true) ?? [];
        }

        if (isset($data['interests']) && is_string($data['interests'])) {
            $data['interests'] = json_decode($data['interests'], true) ?? [];
        }

        return $data;
    }
}
