<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neo4j Laravel Integration Demo</title>
    <script src="https://unpkg.com/vis-network/standalone/umd/vis-network.min.js"></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            padding: 20px;
        }
        .section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .section h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        #network {
            height: 400px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background: white;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-success {
            background: #48bb78;
        }
        .btn-success:hover {
            background: #38a169;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .log {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .stats-container {
            background: white;
            border-radius: 8px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            max-height: 300px;
            overflow-y: auto;
        }
        .type-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            margin: 4px 0;
            background: #f7fafc;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        .type-label {
            font-weight: 500;
            color: #2d3748;
        }
        .type-count {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .summary-stats {
            background: #edf2f7;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #4a5568;
        }
        .error {
            color: #e53e3e;
            font-style: italic;
            text-align: center;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔗 Neo4j Laravel Integration Demo</h1>
            <p>Explore graph database functionality with Laravel and Neo4j</p>
        </div>

        <div class="content">
            <!-- Database Stats -->
            <div class="section full-width">
                <h3>📊 Database Statistics</h3>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number" id="nodeCount">-</div>
                        <div class="stat-label">Total Nodes</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="relationshipCount">-</div>
                        <div class="stat-label">Relationships</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="dbVersion">-</div>
                        <div class="stat-label">Neo4j Version</div>
                    </div>
                </div>
            </div>

            <!-- Add Person Form -->
            <div class="section">
                <h3>👤 Add Person</h3>
                <form id="personForm">
                    <div class="form-group">
                        <label for="name">Name:</label>
                        <input type="text" id="name" required>
                    </div>
                    <div class="form-group">
                        <label for="age">Age:</label>
                        <input type="number" id="age" min="0">
                    </div>
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email">
                    </div>
                    <button type="submit" class="btn btn-success">Add Person</button>
                </form>
            </div>

            <!-- Add Relationship Form -->
            <div class="section">
                <h3>🔗 Add Relationship</h3>
                <form id="relationshipForm">
                    <div class="form-group">
                        <label for="person1">Person 1:</label>
                        <select id="person1" required>
                            <option value="">Select person...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="person2">Person 2:</label>
                        <select id="person2" required>
                            <option value="">Select person...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="relType">Relationship Type:</label>
                        <select id="relType" required>
                            <option value="">Select type...</option>
                            <option value="FRIENDS_WITH">Friends With</option>
                            <option value="WORKS_WITH">Works With</option>
                            <option value="FAMILY_OF">Family Of</option>
                            <option value="MANAGES">Manages</option>
                            <option value="REPORTS_TO">Reports To</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Add Relationship</button>
                </form>
            </div>

            <!-- Node Types & Statistics -->
            <div class="section full-width">
                <h3>📊 Node Types & Statistics</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4>🏷️ Node Types</h4>
                        <div id="nodeTypesContainer" class="stats-container">
                            <p>Loading node types...</p>
                        </div>
                    </div>
                    <div>
                        <h4>🔗 Relationship Types</h4>
                        <div id="relationshipTypesContainer" class="stats-container">
                            <p>Loading relationship types...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Network Visualization -->
            <div class="section full-width">
                <h3>🌐 Network Graph</h3>
                <div>
                    <button class="btn" onclick="refreshNetwork()">🔄 Refresh Network</button>
                    <button class="btn" onclick="fitNetwork()">📐 Fit to Screen</button>
                </div>
                <div id="network"></div>
            </div>

            <!-- Activity Log -->
            <div class="section full-width">
                <h3>📝 Activity Log</h3>
                <div id="log" class="log"></div>
            </div>
        </div>
    </div>

    <script>
        let network;
        let nodes, edges;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            log('Application initialized');
            loadStats();
            loadNodeTypes();
            loadPersons();
            initNetwork();
        });

        // Logging function
        function log(message) {
            const logElement = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            logElement.innerHTML += `[${timestamp}] ${message}\n`;
            logElement.scrollTop = logElement.scrollHeight;
        }

        // Load database statistics
        async function loadStats() {
            try {
                const response = await fetch('/api/neo4j');
                const data = await response.json();
                
                if (data.status === 'success') {
                    document.getElementById('nodeCount').textContent = data.nodeCount;
                    document.getElementById('dbVersion').textContent = data.database.versions[0];
                    log(`Loaded stats: ${data.nodeCount} nodes`);
                }
            } catch (error) {
                log(`Error loading stats: ${error.message}`);
            }
        }

        // Load node types and relationship types
        async function loadNodeTypes() {
            try {
                const response = await fetch('/api/neo4j/node-types');
                const data = await response.json();
                
                if (data.status === 'success') {
                    displayNodeTypes(data.data);
                    log(`Loaded ${data.data.uniqueNodeTypes} node types and ${data.data.uniqueRelationshipTypes} relationship types`);
                } else {
                    document.getElementById('nodeTypesContainer').innerHTML = '<p class="error">Failed to load node types</p>';
                    document.getElementById('relationshipTypesContainer').innerHTML = '<p class="error">Failed to load relationship types</p>';
                }
            } catch (error) {
                log(`Error loading node types: ${error.message}`);
                document.getElementById('nodeTypesContainer').innerHTML = '<p class="error">Error loading data</p>';
                document.getElementById('relationshipTypesContainer').innerHTML = '<p class="error">Error loading data</p>';
            }
        }

        // Display node types and relationship types
        function displayNodeTypes(data) {
            // Display node types
            const nodeTypesContainer = document.getElementById('nodeTypesContainer');
            let nodeTypesHtml = `
                <div class="summary-stats">
                    📊 <strong>${data.uniqueNodeTypes}</strong> unique node types | 
                    <strong>${data.totalNodes}</strong> total nodes
                </div>
            `;
            
            if (data.nodeTypes.length > 0) {
                data.nodeTypes.forEach(nodeType => {
                    nodeTypesHtml += `
                        <div class="type-item">
                            <span class="type-label">${nodeType.displayName}</span>
                            <span class="type-count">${nodeType.count}</span>
                        </div>
                    `;
                });
            } else {
                nodeTypesHtml += '<p>No node types found</p>';
            }
            nodeTypesContainer.innerHTML = nodeTypesHtml;

            // Display relationship types
            const relationshipTypesContainer = document.getElementById('relationshipTypesContainer');
            let relationshipTypesHtml = `
                <div class="summary-stats">
                    🔗 <strong>${data.uniqueRelationshipTypes}</strong> unique relationship types | 
                    <strong>${data.totalRelationships}</strong> total relationships
                </div>
            `;
            
            if (data.relationshipTypes.length > 0) {
                data.relationshipTypes.forEach(relType => {
                    relationshipTypesHtml += `
                        <div class="type-item">
                            <span class="type-label">${relType.displayName}</span>
                            <span class="type-count">${relType.count}</span>
                        </div>
                    `;
                });
            } else {
                relationshipTypesHtml += '<p>No relationship types found</p>';
            }
            relationshipTypesContainer.innerHTML = relationshipTypesHtml;
        }

        // Load persons for dropdowns
        async function loadPersons() {
            try {
                const response = await fetch('/api/neo4j/persons');
                const data = await response.json();
                
                if (data.status === 'success') {
                    const person1Select = document.getElementById('person1');
                    const person2Select = document.getElementById('person2');
                    
                    // Clear existing options
                    person1Select.innerHTML = '<option value="">Select person...</option>';
                    person2Select.innerHTML = '<option value="">Select person...</option>';
                    
                    // Add person options
                    data.persons.forEach(person => {
                        const option1 = new Option(person.properties.name, person.id);
                        const option2 = new Option(person.properties.name, person.id);
                        person1Select.add(option1);
                        person2Select.add(option2);
                    });
                    
                    log(`Loaded ${data.persons.length} persons`);
                }
            } catch (error) {
                log(`Error loading persons: ${error.message}`);
            }
        }

        // Initialize network visualization
        function initNetwork() {
            const container = document.getElementById('network');
            
            nodes = new vis.DataSet([]);
            edges = new vis.DataSet([]);
            
            const data = { nodes: nodes, edges: edges };
            const options = {
                nodes: {
                    shape: 'dot',
                    size: 20,
                    font: { size: 14, color: '#333' },
                    borderWidth: 2,
                    color: {
                        border: '#667eea',
                        background: '#a5b4fc'
                    }
                },
                edges: {
                    width: 2,
                    color: { color: '#848484' },
                    font: { size: 12, align: 'middle' },
                    arrows: { to: true }
                },
                physics: {
                    enabled: true,
                    stabilization: { iterations: 100 }
                }
            };
            
            network = new vis.Network(container, data, options);
            log('Network visualization initialized');
            
            // Load initial network data
            refreshNetwork();
        }

        // Refresh network data
        async function refreshNetwork() {
            try {
                const response = await fetch('/api/neo4j/network');
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Clear existing data
                    nodes.clear();
                    edges.clear();
                    
                    // Add nodes
                    const nodeData = data.network.nodes.map(node => ({
                        id: node.id,
                        label: node.label,
                        title: `${node.label}\nAge: ${node.properties.age}\nEmail: ${node.properties.email}`
                    }));
                    nodes.add(nodeData);
                    
                    // Add edges
                    const edgeData = data.network.relationships.map(rel => ({
                        id: rel.id,
                        from: rel.source,
                        to: rel.target,
                        label: rel.type.replace('_', ' ')
                    }));
                    edges.add(edgeData);
                    
                    document.getElementById('relationshipCount').textContent = data.network.relationships.length;
                    log(`Network updated: ${nodeData.length} nodes, ${edgeData.length} relationships`);
                }
            } catch (error) {
                log(`Error refreshing network: ${error.message}`);
            }
        }

        // Fit network to screen
        function fitNetwork() {
            if (network) {
                network.fit();
                log('Network fitted to screen');
            }
        }

        // Handle person form submission
        document.getElementById('personForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                name: document.getElementById('name').value,
                age: parseInt(document.getElementById('age').value) || null,
                email: document.getElementById('email').value || null
            };
            
            try {
                const response = await fetch('/api/neo4j/persons', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    log(`Person created: ${formData.name}`);
                    this.reset();
                    loadStats();
                    loadNodeTypes();
                    loadPersons();
                    refreshNetwork();
                } else {
                    log(`Error creating person: ${data.message}`);
                }
            } catch (error) {
                log(`Error creating person: ${error.message}`);
            }
        });

        // Handle relationship form submission
        document.getElementById('relationshipForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                person1_id: parseInt(document.getElementById('person1').value),
                person2_id: parseInt(document.getElementById('person2').value),
                relationship_type: document.getElementById('relType').value
            };
            
            try {
                const response = await fetch('/api/neo4j/relationships', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    log(`Relationship created: ${formData.relationship_type}`);
                    this.reset();
                    loadStats();
                    loadNodeTypes();
                    refreshNetwork();
                } else {
                    log(`Error creating relationship: ${data.message}`);
                }
            } catch (error) {
                log(`Error creating relationship: ${error.message}`);
            }
        });
    </script>
</body>
</html>
