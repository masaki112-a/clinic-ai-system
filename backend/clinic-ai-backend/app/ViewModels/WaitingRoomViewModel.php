<?php

namespace App\ViewModels;

use App\Services\WaitingRoomService;
use App\Enums\VisitState;
use App\Models\Visit;

class WaitingRoomViewModel
{
    public int $waitingCount;
    public ?string $callingVisitCode;
    public ?string $inExamVisitCode;
    public int $estimatedWaitMinutes;

    public function __construct(WaitingRoomService $service)
    {
        // S2件数はService経由
        $this->waitingCount = $service->countWaiting();

        // S3・S4はまだService化していないので今回は許容（STEP逸脱回避）
        $calling = Visit::where(
            'current_state',
            VisitState::S3->value
        )->latest()->first();

        $inExam = Visit::where(
            'current_state',
            VisitState::S4->value
        )->latest()->first();

        $this->callingVisitCode = $calling?->visit_code;
        $this->inExamVisitCode  = $inExam?->visit_code;

        $averageExamMinutes = config('clinic.average_exam_minutes', 10);

        $this->estimatedWaitMinutes =
            $this->waitingCount * $averageExamMinutes;
    }
}
