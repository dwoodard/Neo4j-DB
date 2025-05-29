<?php

namespace App\Models;

use App\Graph\GraphModel;

class TestCategory extends GraphModel
{
    protected static string $label = 'TestCategory';

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