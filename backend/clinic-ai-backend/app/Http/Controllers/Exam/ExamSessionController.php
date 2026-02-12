<?php

namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\ExamSessionStateService;
use Illuminate\Http\RedirectResponse;

class ExamSessionController extends Controller
{
    public function start(
        Visit $visit,
        ExamSessionStateService $service
    ): RedirectResponse {
        $service->startExam($visit);
        return redirect()->back();
    }

    public function end(
        Visit $visit,
        ExamSessionStateService $service
    ): RedirectResponse {
        $service->endExam($visit);
        return redirect()->back();
    }
}
