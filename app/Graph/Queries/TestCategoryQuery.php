<?php

namespace App\Graph\Queries;

use App\Graph\GraphQueryBuilder;
use App\Models\TestCategory;

class TestCategoryQuery
{
    /**
     * Custom query methods for TestCategory
     */
    
    /**
     * Example: Find active TestCategory records
     */
    public static function active(): GraphQueryBuilder
    {
        return TestCategory::where('active', '=', true);
    }
    
    /**
     * Example: Search TestCategory by name
     */
    public static function searchByName(string $term): GraphQueryBuilder
    {
        return TestCategory::where('name', 'CONTAINS', $term);
    }
    
    /**
     * Add more custom query logic here
     */
}