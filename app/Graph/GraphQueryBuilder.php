<?php

namespace App\Graph;

use App\Services\Neo4jService;
use Illuminate\Support\Collection;

class GraphQueryBuilder
{
    protected string $model;

    protected array $wheres = [];

    protected array $orderBy = [];

    protected ?int $limit = null;

    protected ?int $skip = null;

    protected array $with = [];

    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * Forward scope calls to the model
     */
    public function __call(string $name, array $arguments): self
    {
        if (method_exists($this->model, $name)) {
            $result = $this->model::$name(...$arguments);

            if ($result instanceof self) {
                // Merge the query conditions from the scope
                $this->wheres = array_merge($this->wheres, $result->wheres);
                $this->orderBy = array_merge($this->orderBy, $result->orderBy);

                if ($result->limit !== null) {
                    $this->limit = $result->limit;
                }

                if ($result->skip !== null) {
                    $this->skip = $result->skip;
                }

                return $this;
            }
        }

        throw new \BadMethodCallException("Call to undefined method {$name}");
    }

    /**
     * Add a where clause
     */
    public function where(string $key, string $operator, mixed $value = null): self
    {
        // Handle where('key', 'value') syntax
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('key', 'operator', 'value');

        return $this;
    }

    /**
     * Add an OR where clause
     */
    public function orWhere(string $key, string $operator, mixed $value = null): self
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = compact('key', 'operator', 'value') + ['boolean' => 'OR'];

        return $this;
    }

    /**
     * Where value is in array
     */
    public function whereIn(string $key, array $values): self
    {
        $this->wheres[] = [
            'key' => $key,
            'operator' => 'IN',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * Where value is not in array
     */
    public function whereNotIn(string $key, array $values): self
    {
        $this->wheres[] = [
            'key' => $key,
            'operator' => 'NOT IN',
            'value' => $values,
        ];

        return $this;
    }

    /**
     * Where value is null
     */
    public function whereNull(string $key): self
    {
        $this->wheres[] = [
            'key' => $key,
            'operator' => 'IS NULL',
            'value' => null,
        ];

        return $this;
    }

    /**
     * Where value is not null
     */
    public function whereNotNull(string $key): self
    {
        $this->wheres[] = [
            'key' => $key,
            'operator' => 'IS NOT NULL',
            'value' => null,
        ];

        return $this;
    }

    /**
     * Add an order by clause
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = compact('column', 'direction');

        return $this;
    }

    /**
     * Order by descending
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Limit the results
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Take a specific number of results
     */
    public function take(int $count): self
    {
        return $this->limit($count);
    }

    /**
     * Skip a number of results
     */
    public function skip(int $skip): self
    {
        $this->skip = $skip;

        return $this;
    }

    /**
     * Execute the query and get results
     */
    public function get(): Collection
    {
        $label = $this->model::getLabel();
        $service = app(Neo4jService::class);

        $query = $this->buildQuery();
        $params = $this->buildParams();

        $result = $service->runQuery($query, $params);

        return collect($result)
            ->map(function ($record) {
                $nodeData = $record->get('n')->getProperties()->toArray();
                $nodeId = $record->get('neo4j_id');
                $nodeData['id'] = $nodeId;

                return new $this->model($nodeData);
            });
    }

    /**
     * Get the first result
     */
    public function first(): ?GraphModel
    {
        $results = $this->limit(1)->get();

        return $results->first();
    }

    /**
     * Get the first result or throw exception
     */
    public function firstOrFail(): GraphModel
    {
        $result = $this->first();

        if ($result === null) {
            throw new \Exception("No results found for {$this->model}");
        }

        return $result;
    }

    /**
     * Count the results
     */
    public function count(): int
    {
        $label = $this->model::getLabel();
        $service = app(Neo4jService::class);

        $whereClause = $this->buildWhereClause();
        $params = $this->buildParams();

        $query = "MATCH (n:$label)";
        if (! empty($whereClause)) {
            $query .= " WHERE $whereClause";
        }
        $query .= ' RETURN count(n) as count';

        $result = $service->runQuery($query, $params);

        return $result->first()->get('count');
    }

    /**
     * Check if any results exist
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Paginate results
     */
    public function paginate(int $perPage = 15, int $page = 1): array
    {
        $total = $this->count();
        $skip = ($page - 1) * $perPage;

        $results = $this->skip($skip)->limit($perPage)->get();

        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $skip + 1,
            'to' => min($skip + $perPage, $total),
        ];
    }

    /**
     * Build the complete Cypher query
     */
    protected function buildQuery(): string
    {
        $label = $this->model::getLabel();
        $query = "MATCH (n:$label)";

        $whereClause = $this->buildWhereClause();
        if (! empty($whereClause)) {
            $query .= " WHERE $whereClause";
        }

        $query .= ' RETURN n, id(n) as neo4j_id';

        if (! empty($this->orderBy)) {
            $orderClauses = [];
            foreach ($this->orderBy as $order) {
                $orderClauses[] = "n.{$order['column']} {$order['direction']}";
            }
            $query .= ' ORDER BY '.implode(', ', $orderClauses);
        }

        if ($this->skip !== null) {
            $query .= " SKIP {$this->skip}";
        }

        if ($this->limit !== null) {
            $query .= " LIMIT {$this->limit}";
        }

        return $query;
    }

    /**
     * Build the WHERE clause
     */
    protected function buildWhereClause(): string
    {
        if (empty($this->wheres)) {
            return '';
        }

        $conditions = [];
        foreach ($this->wheres as $i => $where) {
            $boolean = $where['boolean'] ?? 'AND';
            $condition = $this->buildWhereCondition($where, $i);

            if ($i === 0) {
                $conditions[] = $condition;
            } else {
                $conditions[] = " $boolean $condition";
            }
        }

        return implode('', $conditions);
    }

    /**
     * Build a single WHERE condition
     */
    protected function buildWhereCondition(array $where, int $index): string
    {
        $key = $where['key'];
        $operator = $where['operator'];
        $paramKey = "param$index";

        return match ($operator) {
            'IS NULL' => "n.$key IS NULL",
            'IS NOT NULL' => "n.$key IS NOT NULL",
            'IN' => "n.$key IN \$$paramKey",
            'NOT IN' => "NOT n.$key IN \$$paramKey",
            'CONTAINS' => "n.$key CONTAINS \$$paramKey",
            'STARTS WITH' => "n.$key STARTS WITH \$$paramKey",
            'ENDS WITH' => "n.$key ENDS WITH \$$paramKey",
            'SEARCH_CI' => "toLower(n.$key) CONTAINS toLower(\$$paramKey)",
            default => "n.$key $operator \$$paramKey"
        };
    }

    /**
     * Build query parameters
     */
    protected function buildParams(): array
    {
        $params = [];
        foreach ($this->wheres as $i => $where) {
            if (! in_array($where['operator'], ['IS NULL', 'IS NOT NULL'])) {
                $params["param$i"] = $where['value'];
            }
        }

        return $params;
    }

    /**
     * Add text search capability (case-insensitive)
     */
    public function search(string $term, array $fields = ['name']): self
    {
        foreach ($fields as $field) {
            $this->where($field, 'SEARCH_CI', $term);
        }

        return $this;
    }

    /**
     * Add relationship filtering
     */
    public function hasRelationship(string $relationshipType, string $direction = 'out'): self
    {
        $this->with[] = [
            'type' => 'relationship',
            'relationship_type' => $relationshipType,
            'direction' => $direction,
        ];

        return $this;
    }

    /**
     * Clone the query builder
     */
    public function clone(): self
    {
        $clone = new static($this->model);
        $clone->wheres = $this->wheres;
        $clone->orderBy = $this->orderBy;
        $clone->limit = $this->limit;
        $clone->skip = $this->skip;
        $clone->with = $this->with;

        return $clone;
    }
}
