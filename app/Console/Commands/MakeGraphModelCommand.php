<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeGraphModelCommand extends Command
{
    protected $signature = 'make:graph-model {name} {--a|all : Generate with related classes}';

    protected $description = 'Create a new GraphModel and optionally related classes';

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $all = $this->option('all');

        $this->makeModel($name);

        if ($all) {
            $this->makeQueryClass($name);
            $this->makeRelationsClass($name);
            $this->makeTestClass($name);
            $this->makeSeederClass($name);
        }

        $this->info("GraphModel {$name} created successfully!");

        return 0;
    }

    protected function makeModel(string $name): void
    {
        $path = app_path("Models/{$name}.php");

        if (File::exists($path)) {
            $this->warn("Model already exists: {$name}");

            return;
        }

        File::ensureDirectoryExists(app_path('Models'));

        File::put($path, <<<PHP
<?php

namespace App\Models;

use App\Graph\GraphModel;

class {$name} extends GraphModel
{
    protected static string \$label = '{$name}';

    protected array \$fillable = [
        // Add your fillable attributes here
    ];

    protected array \$casts = [
        // Add your casts here
        // 'active' => 'boolean',
        // 'age' => 'integer',
        // 'skills' => 'array',
    ];
}
PHP);

        $this->line("✅ Created model: app/Models/{$name}.php");
    }

    protected function makeQueryClass(string $name): void
    {
        $path = app_path("Graph/Queries/{$name}Query.php");

        File::ensureDirectoryExists(app_path('Graph/Queries'));

        File::put($path, <<<PHP
<?php

namespace App\Graph\Queries;

use App\Graph\GraphQueryBuilder;
use App\Models\\{$name};

class {$name}Query
{
    /**
     * Custom query methods for {$name}
     */
    
    /**
     * Example: Find active {$name} records
     */
    public static function active(): GraphQueryBuilder
    {
        return {$name}::where('active', '=', true);
    }
    
    /**
     * Example: Search {$name} by name
     */
    public static function searchByName(string \$term): GraphQueryBuilder
    {
        return {$name}::where('name', 'CONTAINS', \$term);
    }
    
    /**
     * Add more custom query logic here
     */
}
PHP);

        $this->line("✅ Created query class: app/Graph/Queries/{$name}Query.php");
    }

    protected function makeRelationsClass(string $name): void
    {
        $path = app_path("Graph/Relations/{$name}Relations.php");

        File::ensureDirectoryExists(app_path('Graph/Relations'));

        $lowerName = Str::lower($name);

        File::put($path, <<<PHP
<?php

namespace App\Graph\Relations;

use Illuminate\Support\Collection;

trait {$name}Relations
{
    /**
     * Define relationships for {$name}
     */
    
    /**
     * Example: Get related items
     */
    public function getRelated{$name}s(): Collection
    {
        return \$this->getRelated('RELATED_TO', 'both');
    }
    
    /**
     * Example: Create a relationship
     */
    public function relatedTo(\$target, array \$properties = []): bool
    {
        return \$this->createRelationshipTo(\$target, 'RELATED_TO', \$properties);
    }
    
    /**
     * Add more relationship methods here
     * Examples:
     * - friends, colleagues, managers
     * - belongs_to, has_many type relationships
     * - custom domain-specific relationships
     */
}
PHP);

        $this->line("✅ Created relations trait: app/Graph/Relations/{$name}Relations.php");
    }

    protected function makeTestClass(string $name): void
    {
        $path = base_path("tests/Graph/{$name}Test.php");

        File::ensureDirectoryExists(base_path('tests/Graph'));

        $lowerName = Str::lower($name);

        File::put($path, <<<PHP
<?php

use App\Models\\{$name};

describe('{$name} Model', function () {
    it('can create a {$name}', function () {
        \$model = {$name}::create([
            'name' => 'Test {$name}',
            // Add other required attributes
        ]);

        expect(\$model->toArray())->toHaveKey('name');
        expect(\$model->name)->toBe('Test {$name}');
    });

    it('can find a {$name} by id', function () {
        \$model = {$name}::create([
            'name' => 'Findable {$name}',
        ]);

        \$found = {$name}::find(\$model->getId());

        expect(\$found)->not()->toBeNull();
        expect(\$found->name)->toBe('Findable {$name}');
    });

    it('can update a {$name}', function () {
        \$model = {$name}::create([
            'name' => 'Original Name',
        ]);

        \$model->name = 'Updated Name';
        \$model->save();

        \$updated = {$name}::find(\$model->getId());
        expect(\$updated->name)->toBe('Updated Name');
    });

    it('can delete a {$name}', function () {
        \$model = {$name}::create([
            'name' => 'To Be Deleted',
        ]);

        \$id = \$model->getId();
        \$model->delete();

        \$deleted = {$name}::find(\$id);
        expect(\$deleted)->toBeNull();
    });

    it('can query {$name} records', function () {
        {$name}::create(['name' => 'First {$name}']);
        {$name}::create(['name' => 'Second {$name}']);

        \$all = {$name}::all();
        expect(\$all->count())->toBeGreaterThanOrEqual(2);

        \$filtered = {$name}::where('name', 'CONTAINS', 'First')->get();
        expect(\$filtered->count())->toBeGreaterThanOrEqual(1);
    });
});
PHP);

        $this->line("✅ Created test file: tests/Graph/{$name}Test.php");
    }

    protected function makeSeederClass(string $name): void
    {
        $path = base_path("database/seeders/{$name}Seeder.php");

        File::ensureDirectoryExists(base_path('database/seeders'));

        $lowerName = Str::lower($name);
        $pluralName = Str::plural($lowerName);

        File::put($path, <<<PHP
<?php

namespace Database\Seeders;

use App\Models\\{$name};
use Illuminate\Database\Seeder;

class {$name}Seeder extends Seeder
{
    /**
     * Seed the database with {$name} data.
     */
    public function run(): void
    {
        \$this->command->info('🌱 Seeding {$name} data...');

        // Create sample {$pluralName}
        \$sample{$name}s = [
            [
                'name' => 'Sample {$name} 1',
                // Add other required attributes
            ],
            [
                'name' => 'Sample {$name} 2',
                // Add other required attributes
            ],
            [
                'name' => 'Sample {$name} 3',
                // Add other required attributes
            ],
        ];

        foreach (\$sample{$name}s as \$data) {
            {$name}::create(\$data);
            \$this->command->info("   Created {$lowerName}: {\$data['name']}");
        }

        \$this->command->info("✅ {$name} seeding completed!");
    }
}
PHP);

        $this->line("✅ Created seeder: database/seeders/{$name}Seeder.php");
    }
}
