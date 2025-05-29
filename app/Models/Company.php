<?php

namespace App\Models;

use App\Graph\GraphModel;
use Illuminate\Support\Collection;

class Company extends GraphModel
{
    protected static string $label = 'Company';

    protected array $fillable = [
        'name',
        'industry',
        'size',
        'founded_year',
        'headquarters',
        'website',
        'description',
        'revenue',
        'stock_symbol',
        'ceo',
        'active',
    ];

    protected array $casts = [
        'founded_year' => 'integer',
        'revenue' => 'float',
        'active' => 'boolean',
        'size' => 'integer',
    ];

    /**
     * Get all employees of this company
     */
    public function getEmployees(): Collection
    {
        return Person::where('company', '=', $this->getAttribute('name'))->get();
    }

    /**
     * Get employees by department
     */
    public function getEmployeesByDepartment(string $department): Collection
    {
        return Person::where('company', '=', $this->getAttribute('name'))
            ->where('department', '=', $department)
            ->get();
    }

    /**
     * Get company statistics
     */
    public function getStats(): array
    {
        $employees = $this->getEmployees();

        return [
            'total_employees' => $employees->count(),
            'average_age' => $employees->avg('age'),
            'departments' => $employees->pluck('department')->unique()->values()->toArray(),
            'average_salary' => $employees->avg('salary'),
            'age_groups' => $employees->groupBy(function ($person) {
                return $person->getAgeGroup();
            })->map->count()->toArray(),
        ];
    }

    /**
     * Add an employee relationship
     */
    public function hireEmployee(Person $person, array $details = []): bool
    {
        return $this->createRelationshipTo($person, 'EMPLOYS', $details);
    }

    /**
     * Get company size category
     */
    public function getSizeCategory(): string
    {
        $size = $this->getAttribute('size') ?? 0;

        return match (true) {
            $size < 10 => 'Startup',
            $size < 50 => 'Small',
            $size < 250 => 'Medium',
            $size < 1000 => 'Large',
            default => 'Enterprise'
        };
    }

    /**
     * Scope for finding companies by industry
     */
    public static function inIndustry(string $industry): \App\Graph\GraphQueryBuilder
    {
        return static::where('industry', '=', $industry);
    }

    /**
     * Scope for finding companies by size range
     */
    public static function sizeRange(int $min, int $max): \App\Graph\GraphQueryBuilder
    {
        return static::where('size', '>=', $min)->where('size', '<=', $max);
    }

    /**
     * Scope for active companies
     */
    public static function active(): \App\Graph\GraphQueryBuilder
    {
        return static::where('active', '=', true);
    }
}
