<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\UiExitController;
use App\Http\Controllers\Admin\UiLockController;
use App\Http\Controllers\Exam\ExamController;
use App\Http\Controllers\Exam\ExamSessionController;
use App\Http\Controllers\WaitingRoom\WaitingRoomController;
use App\Http\Controllers\Reception\ReceptionController;


Route::get('/', [HomeController::class, 'index'])->name('home');

Route::post('/home/launch', [HomeController::class, 'launch'])
    ->name('home.launch');

Route::post('/ui/exit', [UiExitController::class, 'exit'])
    ->name('ui.exit');

Route::prefix('reception')->name('reception.')->group(function () {
    Route::get('/', [ReceptionController::class, 'index'])->name('reception');
    // 受付（S0 → S1）
    Route::post('/accept', [ReceptionController::class, 'accept'])
        ->name('accept');
    // 待機投入（S1 → S2）
    Route::post('/enqueue/{visit}', [ReceptionController::class, 'enqueue'])
        ->name('enqueue');
});

Route::prefix('waiting')->group(function () {
Route::get('/', [WaitingRoomController::class, 'index'])->name('waiting');
Route::post('/call-next', [WaitingRoomController::class, 'callNext'])->name('waiting.callNext');
Route::post('/start-exam/{visit}', [WaitingRoomController::class, 'startExam'])->name('waiting.startExam');
});

Route::prefix('admin')->group(function () {
    Route::get('/', fn () => view('index'))->name('admin');
    Route::get('/locks', [UiLockController::class, 'index'])->name('admin.locks');
    Route::post('/locks/force-unlock', [UiLockController::class, 'forceUnlock'])
    ->name('admin.locks.forceUnlock');
});


Route::prefix('exam')->group(function () {
    Route::get('/', [ExamController::class, 'index'])->name('exam');
    Route::post('/start', [ExamController::class, 'start'])->name('exam.start');
    Route::post('/end', [ExamController::class, 'end'])->name('exam.end');
});

Route::prefix('exam-session')->group(function () {
    Route::post('{visit}/start',  [ExamSessionController::class, 'start']);
    Route::post('{visit}/end',    [ExamSessionController::class, 'end']);
});

//開発用
Route::post('/exam/reset', [ExamController::class, 'reset'])
    ->name('exam.reset');
