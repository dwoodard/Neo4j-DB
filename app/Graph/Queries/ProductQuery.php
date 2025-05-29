<?php

namespace App\Graph\Queries;

use App\Graph\GraphQueryBuilder;
use App\Models\Product;

class ProductQuery
{
    /**
     * Custom query methods for Product
     */
    
    /**
     * Example: Find active Product records
     */
    public static function active(): GraphQueryBuilder
    {
        return Product::where('active', '=', true);
    }
    
    /**
     * Example: Search Product by name
     */
    public static function searchByName(string $term): GraphQueryBuilder
    {
        return Product::where('name', 'CONTAINS', $term);
    }
    
    /**
     * Add more custom query logic here
     */
}