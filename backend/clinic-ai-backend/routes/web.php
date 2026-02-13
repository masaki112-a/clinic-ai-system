<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| このアプリケーションはAPIファースト設計です。
| Web routesは最小限に留め、主要な機能は全てAPI経由で提供されます。
|
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Clinic AI System API',
        'version' => '1.0.0',
        'documentation' => '/api/documentation',
    ]);
});
