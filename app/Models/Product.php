<?php

namespace App\Models;

use App\Graph\GraphModel;

class Product extends GraphModel
{
    protected static string $label = 'Product';

    protected array $fillable = [
        // Add your fillable attributes here
    ];

    protected array $casts = [
        // Add your casts here
        // 'active' => 'boolean',
        // 'age' => 'integer',
        // 'skills' => 'array',
    ];
}