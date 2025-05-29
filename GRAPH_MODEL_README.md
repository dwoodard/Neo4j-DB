# Neo4j Graph Model System

A powerful Eloquent-like ORM for Neo4j graph databases in Laravel. This system provides a familiar interface for working with graph data while leveraging the full power of Neo4j's graph capabilities.

## 🚀 Features

- **Eloquent-like Syntax**: Familiar Laravel model interface
- **Graph Relationships**: Native support for Neo4j relationships
- **Query Builder**: Comprehensive query building with method chaining
- **Type Casting**: Automatic attribute casting and validation
- **Relationship Management**: Easy creation and querying of graph relationships
- **Advanced Queries**: Search, pagination, aggregation, and complex filtering
- **Model Scopes**: Reusable query scopes for common patterns

## 📋 Quick Start

### Basic Model Definition

```php
<?php

namespace App\Models;

use App\Graph\GraphModel;

class Person extends GraphModel
{
    protected static string $label = 'Person';
    
    protected array $fillable = [
        'name', 'email', 'age', 'occupation', 'company'
    ];
    
    protected array $casts = [
        'age' => 'integer',
        'active' => 'boolean',
        'skills' => 'array'
    ];
}
```

### Basic Operations

```php
// Create a person
$person = Person::create([
    'name' => 'Alice Johnson',
    'email' => 'alice@example.com',
    'age' => 32,
    'occupation' => 'Developer'
]);

// Find by ID
$person = Person::find(123);

// Get all people
$people = Person::all();

// Update
$person->age = 33;
$person->save();

// Delete
$person->delete();
```

### Query Building

```php
// Basic where clauses
$adults = Person::where('age', '>', 18)->get();
$developers = Person::where('occupation', '=', 'Developer')->get();

// Multiple conditions
$seniorDevs = Person::where('age', '>', 30)
                   ->where('occupation', 'CONTAINS', 'Senior')
                   ->get();

// Ordering and limiting
$topEarners = Person::orderByDesc('salary')
                   ->limit(10)
                   ->get();

// Pagination
$page = Person::where('active', true)->paginate(15, 1);

// Search
$results = Person::search('Alice')->get();

// Aggregation
$count = Person::adults()->count();
$exists = Person::where('company', 'TechCorp')->exists();
```

### Advanced Queries

```php
// Array operations
$techPeople = Person::whereIn('occupation', ['Developer', 'Engineer'])->get();

// Null checks
$unemployed = Person::whereNull('company')->get();

// Text operations
$managers = Person::where('title', 'CONTAINS', 'Manager')->get();
$seniors = Person::where('name', 'STARTS WITH', 'Sr.')->get();

// Age ranges
$millennials = Person::ageRange(25, 40)->get();

// Company filtering
$employees = Person::inCompany('TechCorp')->get();
```

## 🔗 Relationships

### Creating Relationships

```php
$alice = Person::find(1);
$bob = Person::find(2);

// Create friendship
$alice->addFriend($bob);

// Create colleague relationship with properties
$alice->addColleague($bob, ['team' => 'Backend', 'since' => '2023-01-01']);

// Management relationship
$manager = Person::find(3);
$manager->manage($alice);
```

### Querying Relationships

```php
// Get friends
$friends = $alice->getFriends();

// Get manager
$manager = $alice->getManager();

// Get direct reports
$reports = $manager->getDirectReports();

// Get colleagues
$colleagues = $alice->getColleagues();
$deptColleagues = $alice->getDepartmentColleagues();

// Generic relationship queries
$related = $alice->getRelated('WORKS_WITH', 'both');
```

## 📊 Model Examples

### Person Model

```php
class Person extends GraphModel
{
    protected static string $label = 'Person';
    
    // Scopes
    public static function adults(): GraphQueryBuilder
    {
        return static::where('age', '>=', 18);
    }
    
    public static function inCompany(string $company): GraphQueryBuilder
    {
        return static::where('company', '=', $company);
    }
    
    // Methods
    public function getAgeGroup(): string
    {
        $age = $this->age;
        return match(true) {
            $age < 18 => 'Minor',
            $age < 25 => 'Young Adult',
            $age < 35 => 'Adult',
            $age < 50 => 'Middle Age',
            $age < 65 => 'Mature',
            default => 'Senior'
        };
    }
    
    public function isAdult(): bool
    {
        return $this->age >= 18;
    }
}
```

### Company Model

```php
class Company extends GraphModel
{
    protected static string $label = 'Company';
    
    public function getEmployees(): Collection
    {
        return Person::where('company', '=', $this->name)->get();
    }
    
    public function getStats(): array
    {
        $employees = $this->getEmployees();
        return [
            'total_employees' => $employees->count(),
            'average_age' => $employees->avg('age'),
            'departments' => $employees->pluck('department')->unique()->values()
        ];
    }
    
    public function getSizeCategory(): string
    {
        return match(true) {
            $this->size < 10 => 'Startup',
            $this->size < 50 => 'Small',
            $this->size < 250 => 'Medium',
            $this->size < 1000 => 'Large',
            default => 'Enterprise'
        };
    }
}
```

### Project Model

```php
class Project extends GraphModel
{
    protected static string $label = 'Project';
    
    public function getTeamMembers(): Collection
    {
        return $this->getRelated('WORKS_ON', 'in');
    }
    
    public function assignPerson(Person $person, array $details = []): bool
    {
        return $person->createRelationshipTo($this, 'WORKS_ON', $details);
    }
    
    public function updateProgress(int $progress): bool
    {
        $this->progress = max(0, min(100, $progress));
        if ($progress >= 100) {
            $this->status = 'Completed';
            $this->end_date = now()->toDateString();
        }
        return $this->save();
    }
    
    public function isOverdue(): bool
    {
        return now()->isAfter($this->deadline) && $this->progress < 100;
    }
}
```

## 🎯 Usage Patterns

### Business Logic Example

```php
// Find all overdue high-priority projects
$urgentProjects = Project::withPriority('High')
                         ->overdue()
                         ->get();

// Get team members for urgent projects
foreach ($urgentProjects as $project) {
    $team = $project->getTeamMembers();
    $manager = $project->getProjectManager();
    
    echo "Project: {$project->name}\n";
    echo "Team size: {$team->count()}\n";
    echo "Manager: {$manager->name}\n";
    echo "Days overdue: " . abs($project->getDaysRemaining()) . "\n\n";
}
```

### Analytics Example

```php
// Company analysis
$companies = Company::active()->get();

foreach ($companies as $company) {
    $stats = $company->getStats();
    
    echo "Company: {$company->name}\n";
    echo "Size: {$company->getSizeCategory()}\n";
    echo "Employees: {$stats['total_employees']}\n";
    echo "Avg Age: {$stats['average_age']}\n";
    echo "Departments: " . implode(', ', $stats['departments']) . "\n\n";
}
```

### Relationship Analysis

```php
// Find well-connected people
$people = Person::all();
$networkAnalysis = [];

foreach ($people as $person) {
    $connections = $person->getFriends()->count() + 
                  $person->getColleagues()->count();
    
    $networkAnalysis[] = [
        'person' => $person->name,
        'connections' => $connections,
        'company' => $person->company,
        'department' => $person->department
    ];
}

// Sort by connection count
usort($networkAnalysis, fn($a, $b) => $b['connections'] <=> $a['connections']);
```

## 🧪 Testing and Demo

### Run the Demo

```bash
# Full demonstration
php artisan neo4j:graph-demo

# Specific scenarios
php artisan neo4j:graph-demo --scenario=basic
php artisan neo4j:graph-demo --scenario=company
php artisan neo4j:graph-demo --scenario=project
php artisan neo4j:graph-demo --scenario=relationships

# Reset demo data
php artisan neo4j:graph-demo --reset
```

### Test the System

```bash
# Basic model testing
php artisan neo4j:test-graph-model

# Specific tests
php artisan neo4j:test-graph-model --action=create --count=20
php artisan neo4j:test-graph-model --action=query
php artisan neo4j:test-graph-model --action=relationships

# Cleanup test data
php artisan neo4j:test-graph-model --action=cleanup
```

## 🔧 Architecture

### Core Components

1. **GraphModel**: Base abstract class providing core functionality
2. **GraphQueryBuilder**: Query builder with method chaining
3. **Model Classes**: Person, Company, Project extend GraphModel
4. **Neo4jService**: Database connection and query execution

### Key Features

- **Automatic Label Inference**: Uses class name as Neo4j label
- **Attribute Casting**: Type conversion for integers, booleans, arrays
- **Relationship Handling**: Graph-native relationship creation and queries
- **Query Optimization**: Efficient Cypher query generation
- **Error Handling**: Comprehensive error handling and validation

### Query Builder Features

- Method chaining for complex queries
- Support for WHERE, ORDER BY, LIMIT, SKIP
- Search functionality across multiple fields
- Pagination with metadata
- Aggregation functions (count, exists)
- Array operations (IN, NOT IN)
- Text operations (CONTAINS, STARTS WITH, ENDS WITH)

## 📈 Performance Considerations

- Use indexes on frequently queried properties
- Leverage relationship directions for efficient traversals
- Batch operations for bulk data creation
- Consider query complexity and optimize Cypher generation
- Use LIMIT for large result sets
- Profile queries in Neo4j Browser for optimization

## 🛠️ Extension Points

The system is designed to be extensible:

- Add new model classes by extending GraphModel
- Create custom scopes for domain-specific queries
- Implement custom casting types
- Add relationship types and methods
- Extend query builder with domain-specific methods

## 📝 Best Practices

1. **Model Design**: Keep models focused and use clear relationship types
2. **Query Optimization**: Use specific WHERE clauses and appropriate limits
3. **Relationship Management**: Use meaningful relationship types and properties
4. **Data Validation**: Implement proper casting and validation rules
5. **Testing**: Use the provided demo and test commands during development
6. **Documentation**: Document custom scopes and relationship methods

This Graph Model system provides a powerful foundation for building graph-based applications with Laravel and Neo4j, combining the familiarity of Eloquent with the power of graph databases.
