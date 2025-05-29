<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/neo4j-demo', function () {
    return view('neo4j-demo');
});
