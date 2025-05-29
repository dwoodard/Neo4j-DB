<?php

use App\Http\Controllers\Neo4jController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Neo4j API Routes
Route::prefix('neo4j')->group(function () {
    Route::get('/', [Neo4jController::class, 'index']);
    Route::get('/persons', [Neo4jController::class, 'getPersons']);
    Route::post('/persons', [Neo4jController::class, 'createPerson']);
    Route::post('/relationships', [Neo4jController::class, 'createRelationship']);
    Route::get('/network', [Neo4jController::class, 'getNetwork']);
});
