<?php

namespace App\Http\Controllers\Exam;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Visit;
use App\Models\UiLock;
use App\Services\UiLockService;
use App\Services\ExamSessionStateService;

class ExamController extends Controller
{
    public function index(): View
    {
        $visit = Visit::whereIn('current_state', ['S3', 'S4'])
            ->latest()
            ->first();

        $session = $visit?->examSession;

        $lock = UiLock::where('ui_name', 'exam_room')->first();

        return view('exam.index', [
            'visit'   => $visit,
            'session' => $session,
            'state'   => $session?->current_state ?? 'idle',
            'lock'    => $lock,
        ]);
    }

    public function start(
        Request $request,
        Visit $visit,
        ExamSessionStateService $service,
        UiLockService $lockService
    ): RedirectResponse {
        $lockService->lock(
            uiName: 'exam_room',
            lockerId: $request->ip()
        );

        $service->startExam($visit);

        return redirect()->route('exam');
    }

    public function end(
        Visit $visit,
        ExamSessionStateService $service,
        UiLockService $lockService
    ): RedirectResponse {
        $service->endExam($visit);

        $lockService->unlock('exam_room');

        return redirect()->route('exam');
    }


    //開発用
    public function reset(): RedirectResponse
    {
        abort_unless(app()->environment('local'), 403);

        $visit = Visit::latest()->first();

        if ($visit) {
            $visit->update([
                'current_state'   => 'S2',
                'exam_started_at' => null,
                'exam_ended_at'   => null,
            ]);

            $visit->examSession?->update([
                'current_state' => 'idle',
                'started_at'    => null,
                'ended_at'      => null,
            ]);
        }

        return redirect()->route('exam');
    }
}
