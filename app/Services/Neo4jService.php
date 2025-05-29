<?php

namespace App\Services;

use Exception;
use Laudis\Neo4j\ClientBuilder;
use Laudis\Neo4j\Contracts\ClientInterface;

class Neo4jService
{
    protected ClientInterface $client;

    public function __construct()
    {
        try {
            $this->client = ClientBuilder::create()
                ->withDriver(
                    'default',
                    sprintf(
                        'bolt://%s:%s@%s:%s',
                        config('services.neo4j.username'),
                        config('services.neo4j.password'),
                        config('services.neo4j.host'),
                        config('services.neo4j.port')
                    )
                )
                ->build();
        } catch (Exception $e) {
            throw new Exception('Failed to connect to Neo4j: '.$e->getMessage());
        }
    }

    /**
     * Run a Cypher query against the Neo4j database
     *
     * @param  string  $query  The Cypher query to execute
     * @param  array  $parameters  Parameters to bind to the query
     * @return \Laudis\Neo4j\Types\CypherList
     */
    public function runQuery(string $query, array $parameters = [])
    {
        try {
            return $this->client->run($query, $parameters);
        } catch (Exception $e) {
            throw new Exception('Query execution failed: '.$e->getMessage());
        }
    }

    /**
     * Run a query and return the first result
     *
     * @return mixed
     */
    public function runQuerySingle(string $query, array $parameters = [])
    {
        $result = $this->runQuery($query, $parameters);

        return $result->first();
    }

    /**
     * Test the connection to Neo4j
     */
    public function testConnection(): bool
    {
        try {
            $this->runQuery('RETURN 1 as test');

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get the client instance
     */
    public function getClient(): ClientInterface
    {
        return $this->client;
    }
}
