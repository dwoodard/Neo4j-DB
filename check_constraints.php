<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $neo4j = app('App\Services\Neo4jService');
    
    echo "Checking for existing constraints...\n";
    
    try {
        $result = $neo4j->runQuery('SHOW CONSTRAINTS');
        $constraintCount = 0;
        
        foreach ($result as $record) {
            $constraintCount++;
            echo "Found constraint: " . json_encode($record->toArray()) . "\n";
        }
        
        if ($constraintCount === 0) {
            echo "No constraints found.\n";
        }
        
    } catch (Exception $e) {
        echo "Error checking constraints: " . $e->getMessage() . "\n";
    }
    
    // Try to drop any person email constraints
    echo "\nAttempting to drop any email uniqueness constraints...\n";
    
    try {
        $neo4j->runQuery('DROP CONSTRAINT person_email_unique IF EXISTS');
        echo "Dropped person_email_unique constraint (if it existed).\n";
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
