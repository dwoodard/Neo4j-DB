<?php

namespace App\Graph;

use App\Services\Neo4jService;
use Illuminate\Support\Collection;

abstract class GraphModel
{
    protected static string $label;

    protected array $attributes = [];

    protected array $original = [];

    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
        $this->exists = ! empty($attributes);
    }

    /**
     * Get the Neo4j label for this model
     */
    public static function getLabel(): string
    {
        return static::$label ?? class_basename(static::class);
    }

    /**
     * Get all nodes of this type
     */
    public static function all(): Collection
    {
        $service = app(Neo4jService::class);

        $query = sprintf('MATCH (n:%s) RETURN n, id(n) as neo4j_id', static::getLabel());
        $result = $service->runQuery($query);

        return collect($result)
            ->map(function ($record) {
                $nodeData = $record->get('n')->getProperties()->toArray();
                $nodeId = $record->get('neo4j_id');
                $nodeData['id'] = $nodeId;

                return new static($nodeData);
            });
    }

    /**
     * Start a query builder chain
     */
    public static function where(string $key, string $operator, mixed $value): GraphQueryBuilder
    {
        return (new GraphQueryBuilder(static::class))->where($key, $operator, $value);
    }

    /**
     * Start a query with a limit
     */
    public static function limit(int $count): GraphQueryBuilder
    {
        return (new GraphQueryBuilder(static::class))->limit($count);
    }

    /**
     * Start a query with ordering
     */
    public static function orderBy(string $column, string $direction = 'ASC'): GraphQueryBuilder
    {
        return (new GraphQueryBuilder(static::class))->orderBy($column, $direction);
    }

    /**
     * Start a query with descending order
     */
    public static function orderByDesc(string $column): GraphQueryBuilder
    {
        return (new GraphQueryBuilder(static::class))->orderByDesc($column);
    }

    /**
     * Get count of all records
     */
    public static function count(): int
    {
        return (new GraphQueryBuilder(static::class))->count();
    }

    /**
     * Start a query with whereIn clause
     */
    public static function whereIn(string $key, array $values): GraphQueryBuilder
    {
        return (new GraphQueryBuilder(static::class))->whereIn($key, $values);
    }

    /**
     * Get the first record
     */
    public static function first(): ?static
    {
        return (new GraphQueryBuilder(static::class))->first();
    }

    /**
     * Find a node by its ID
     */
    public static function find(mixed $id): ?static
    {
        $service = app(Neo4jService::class);
        $label = static::getLabel();

        $query = "MATCH (n:$label) WHERE id(n) = \$id RETURN n, id(n) as neo4j_id";
        $result = $service->runQuery($query, ['id' => $id]);

        if ($result->count() === 0) {
            return null;
        }

        $record = $result->first();
        $nodeData = $record->get('n')->getProperties()->toArray();
        $nodeId = $record->get('neo4j_id');
        $nodeData['id'] = $nodeId;

        return new static($nodeData);
    }

    /**
     * Find a node by a specific attribute
     */
    public static function findBy(string $attribute, mixed $value): ?static
    {
        return static::where($attribute, '=', $value)->first();
    }

    /**
     * Create a new node
     */
    public static function create(array $attributes): static
    {
        $service = app(Neo4jService::class);

        $label = static::getLabel();
        $props = self::buildCypherProps($attributes);

        $query = "CREATE (n:$label $props) RETURN n, id(n) as neo4j_id";
        $result = $service->runQuery($query, $attributes);

        $record = $result->first();
        $nodeData = $record->get('n')->getProperties()->toArray();
        $nodeId = $record->get('neo4j_id');

        // Store the Neo4j ID in the attributes
        $nodeData['id'] = $nodeId;

        return new static($nodeData);
    }

    /**
     * Save the current model
     */
    public function save(): bool
    {
        $service = app(Neo4jService::class);
        $label = static::getLabel();

        if (! $this->exists) {
            // Create new node
            $props = self::buildCypherProps($this->attributes);
            $query = "CREATE (n:$label $props) RETURN n, id(n) as neo4j_id";
            $result = $service->runQuery($query, $this->attributes);

            $record = $result->first();
            $this->attributes = $record->get('n')->getProperties()->toArray();
            $nodeId = $record->get('neo4j_id');
            $this->attributes['id'] = $nodeId;
            $this->original = $this->attributes;
            $this->exists = true;
        } else {
            // Update existing node
            $changes = $this->getDirty();
            if (empty($changes)) {
                return true; // No changes to save
            }

            $setParts = [];
            $params = [];
            foreach ($changes as $key => $value) {
                $setParts[] = "n.$key = \$param_$key";
                $params["param_$key"] = $value;
            }

            $setClause = implode(', ', $setParts);
            $query = "MATCH (n:$label) WHERE id(n) = \$node_id SET $setClause RETURN n";
            $params['node_id'] = $this->getId();

            $result = $service->runQuery($query, $params);
            $this->original = $this->attributes;
        }

        return true;
    }

    /**
     * Delete the current node
     */
    public function delete(): bool
    {
        if (! $this->exists) {
            return false;
        }

        $service = app(Neo4jService::class);
        $label = static::getLabel();

        $query = "MATCH (n:$label) WHERE id(n) = \$id DETACH DELETE n";
        $service->runQuery($query, ['id' => $this->getId()]);

        $this->exists = false;

        return true;
    }

    /**
     * Get the node's internal ID
     */
    public function getId(): ?int
    {
        return $this->attributes['id'] ?? null;
    }

    /**
     * Get an attribute value
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Set an attribute value
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Magic getter for attributes
     */
    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * Magic setter for attributes
     */
    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Check if an attribute exists
     */
    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    /**
     * Get attributes that have been changed
     */
    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Check if the model has been modified
     */
    public function isDirty(): bool
    {
        return ! empty($this->getDirty());
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Build Cypher property string
     */
    protected static function buildCypherProps(array $attributes): string
    {
        $props = array_map(fn ($key) => "$key: \$$key", array_keys($attributes));

        return '{ '.implode(', ', $props).' }';
    }

    /**
     * Create a relationship to another node
     */
    public function createRelationshipTo(GraphModel $target, string $type, array $properties = []): bool
    {
        $service = app(Neo4jService::class);

        $sourceLabel = static::getLabel();
        $targetLabel = $target::getLabel();

        // Check if IDs exist (note: ID 0 is valid in Neo4j)
        $sourceId = $this->getId();
        $targetId = $target->getId();

        if (($sourceId === null) || ($targetId === null)) {
            return false;
        }

        $propString = empty($properties) ? '' : ' '.self::buildCypherProps($properties);

        $query = "
            MATCH (a:$sourceLabel), (b:$targetLabel)
            WHERE id(a) = \$source_id AND id(b) = \$target_id
            CREATE (a)-[r:$type$propString]->(b)
            RETURN r
        ";

        $params = array_merge($properties, [
            'source_id' => $sourceId,
            'target_id' => $targetId,
        ]);

        try {
            $result = $service->runQuery($query, $params);

            return $result->count() > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get related nodes
     */
    public function getRelated(string $relationshipType, string $direction = 'out'): Collection
    {
        $service = app(Neo4jService::class);
        $label = static::getLabel();

        $relationshipPattern = match ($direction) {
            'out' => "-[:$relationshipType]->",
            'in' => "<-[:$relationshipType]-",
            'both' => "-[:$relationshipType]-",
            default => "-[:$relationshipType]->"
        };

        $query = "
            MATCH (n:$label)$relationshipPattern(related)
            WHERE id(n) = \$id
            RETURN related, labels(related) as labels, id(related) as neo4j_id
        ";

        $result = $service->runQuery($query, ['id' => $this->getId()]);

        return collect($result)->map(function ($record) {
            $nodeData = $record->get('related')->getProperties()->toArray();
            $nodeId = $record->get('neo4j_id');
            $nodeData['id'] = $nodeId;
            $labelsObj = $record->get('labels');

            // Convert CypherList to array
            $labels = $labelsObj->toArray();

            // Try to determine the appropriate model class based on labels
            $modelClass = $this->getModelClassFromLabels($labels);

            return new $modelClass($nodeData);
        });
    }

    /**
     * Attempt to get the appropriate model class from Neo4j labels
     */
    protected function getModelClassFromLabels(array $labels): string
    {
        foreach ($labels as $label) {
            $modelClass = "App\\Models\\$label";
            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        // Fallback to a generic graph model or the current class
        return static::class;
    }
}
