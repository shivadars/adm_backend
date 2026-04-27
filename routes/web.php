<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Adoremom API is running',
        'status' => 'healthy',
        'version' => '1.0.0'
    ]);
});
