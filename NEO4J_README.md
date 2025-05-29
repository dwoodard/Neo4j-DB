# Neo4j Laravel Integration

A complete Neo4j graph database integration for Laravel with Docker, REST API, and web interface.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose
- PHP 8.1+
- Composer

### Installation

1. **Clone and Install Dependencies**
```bash
composer install
npm install
```

2. **Environment Setup**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Start Neo4j Database**
```bash
docker-compose up -d
```

4. **Test Connection**
```bash
php artisan neo4j:test
```

5. **Start Laravel Development Server**
```bash
php artisan serve
```

6. **Access Demo Interface**
Visit: http://localhost:8000/neo4j-demo

## 🐳 Neo4j Environment

### Docker Configuration
- **Neo4j Browser**: http://localhost:7474
- **Bolt Protocol**: bolt://localhost:7687
- **Username**: neo4j
- **Password**: neo4j123

### Environment Variables
```env
NEO4J_HOST=localhost
NEO4J_PORT=7687
NEO4J_USERNAME=neo4j
NEO4J_PASSWORD=neo4j123
```

## 📚 API Endpoints

### Database Info
```http
GET /api/neo4j
```

### Persons
```http
GET /api/neo4j/persons              # List all persons
POST /api/neo4j/persons             # Create person
```

**Create Person Payload:**
```json
{
    "name": "John Doe",
    "age": 30,
    "email": "john@example.com"
}
```

### Relationships
```http
POST /api/neo4j/relationships       # Create relationship
```

**Create Relationship Payload:**
```json
{
    "person1_id": 0,
    "person2_id": 1,
    "relationship_type": "FRIENDS_WITH"
}
```

### Network Graph
```http
GET /api/neo4j/network              # Get network graph data
```

## 🛠️ Artisan Commands

### Test Connection
```bash
php artisan neo4j:test
```

### Database Maintenance
```bash
# Show database statistics
php artisan neo4j:maintenance stats

# Clean up orphaned nodes
php artisan neo4j:maintenance cleanup

# Reset entire database (WARNING: Deletes all data)
php artisan neo4j:maintenance reset
```

## 🏗️ Architecture

### Service Layer
- `App\Services\Neo4jService` - Main Neo4j connection and query service
- Singleton pattern for connection management
- Error handling and logging
- Query parameter binding

### Controllers
- `App\Http\Controllers\Neo4jController` - REST API endpoints
- JSON responses with proper error handling
- Input validation

### Commands
- `App\Console\Commands\TestNeo4jConnection` - Connection testing
- `App\Console\Commands\Neo4jMaintenance` - Database maintenance

## 🎨 Web Interface

The demo interface includes:
- **Real-time Statistics** - Node and relationship counts
- **Person Management** - Add new persons with validation
- **Relationship Creation** - Connect persons with different relationship types
- **Network Visualization** - Interactive graph using vis.js
- **Activity Logging** - Real-time operation feedback

## 🔧 Development

### Adding New Node Types
1. Create a new controller method for the node type
2. Add validation rules
3. Create Cypher queries for CRUD operations
4. Add API routes
5. Update the web interface if needed

### Custom Cypher Queries
Use the Neo4jService directly in your controllers:

```php
use App\Services\Neo4jService;

public function customQuery(Neo4jService $neo4j)
{
    $result = $neo4j->runQuery('
        MATCH (p:Person)-[:FRIENDS_WITH]->(f:Person)
        WHERE p.age > $age
        RETURN p.name, collect(f.name) as friends
    ', ['age' => 25]);
    
    return response()->json($result->toArray());
}
```

## 🔐 Production Considerations

### Security
- Change default Neo4j credentials
- Use environment variables for sensitive data
- Implement authentication for API endpoints
- Add rate limiting

### Performance
- Add database indexes for frequently queried properties
- Use connection pooling for high-traffic applications
- Monitor query performance
- Consider read replicas for scaling

### Monitoring
- Enable Neo4j metrics
- Monitor connection pool usage
- Log slow queries
- Set up alerts for connection failures

## 📝 Example Queries

### Find Friends of Friends
```cypher
MATCH (p:Person {name: 'Alice'})-[:FRIENDS_WITH]->(friend)-[:FRIENDS_WITH]->(fof)
WHERE fof <> p
RETURN DISTINCT fof.name as friend_of_friend
```

### Shortest Path Between People
```cypher
MATCH (start:Person {name: 'Alice'}), (end:Person {name: 'Bob'})
MATCH path = shortestPath((start)-[*]-(end))
RETURN path
```

### People with Most Connections
```cypher
MATCH (p:Person)-[r]-()
RETURN p.name, count(r) as connections
ORDER BY connections DESC
LIMIT 10
```

## 🐛 Troubleshooting

### Connection Issues
1. Verify Neo4j container is running: `docker ps`
2. Check container logs: `docker logs neo4j-db`
3. Test connection: `php artisan neo4j:test`
4. Verify environment variables in `.env`

### Common Errors
- **Connection refused**: Neo4j container not running
- **Authentication failed**: Wrong username/password
- **Query syntax errors**: Check Cypher syntax
- **Memory issues**: Increase Docker memory limits

## 📊 Monitoring Commands

```bash
# Check Neo4j container status
docker ps

# View Neo4j logs
docker logs neo4j-db

# Access Neo4j shell
docker exec -it neo4j-db cypher-shell -u neo4j -p neo4j123

# Database statistics
php artisan neo4j:maintenance stats
```

## 🎯 Next Steps

1. **Add Authentication** - Protect API endpoints
2. **Implement Caching** - Cache frequent queries
3. **Add More Node Types** - Extend the data model
4. **Performance Optimization** - Add indexes and optimize queries
5. **Testing** - Add unit and integration tests
6. **Documentation** - API documentation with Swagger/OpenAPI

---

## 📞 Support

For issues and questions:
1. Check the troubleshooting section
2. Review Neo4j documentation
3. Check Laravel logs in `storage/logs/`
4. Verify Docker container health
