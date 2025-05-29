<?php

namespace App\Graph\Relations;

use Illuminate\Support\Collection;

trait TestCategoryRelations
{
    /**
     * Define relationships for TestCategory
     */
    
    /**
     * Example: Get related items
     */
    public function getRelatedTestCategorys(): Collection
    {
        return $this->getRelated('RELATED_TO', 'both');
    }
    
    /**
     * Example: Create a relationship
     */
    public function relatedTo($target, array $properties = []): bool
    {
        return $this->createRelationshipTo($target, 'RELATED_TO', $properties);
    }
    
    /**
     * Add more relationship methods here
     * Examples:
     * - friends, colleagues, managers
     * - belongs_to, has_many type relationships
     * - custom domain-specific relationships
     */
}