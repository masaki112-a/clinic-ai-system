<?php

use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('visits')->group(function () {
    Route::post('accept/qr', [VisitController::class, 'acceptQr']);
    Route::post('accept/manual', [VisitController::class, 'acceptManual']);
});
