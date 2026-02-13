<?php

use App\Http\Controllers\Api\VisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('visits')->group(function () {
    Route::get('/', [VisitController::class, 'index']);
    Route::get('/{id}', [VisitController::class, 'show']);
    Route::post('accept/qr', [VisitController::class, 'acceptQr']);
    Route::post('accept/manual', [VisitController::class, 'acceptManual']);
    Route::post('/{id}/start-waiting', [VisitController::class, 'startWaiting']);
    Route::post('/{id}/call', [VisitController::class, 'call']);
    Route::post('/{id}/enter', [VisitController::class, 'enter']);
    Route::post('/{id}/mark-absent', [VisitController::class, 'markAbsent']);
    Route::post('/{id}/recall', [VisitController::class, 'recall']);
    Route::post('/{id}/complete-exam', [VisitController::class, 'completeExam']);
});
