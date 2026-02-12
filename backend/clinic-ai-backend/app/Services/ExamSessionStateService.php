<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\ExamSession;
use App\Enums\VisitState;
use Illuminate\Support\Facades\DB;
use Exception;

class ExamSessionStateService
{
    public function __construct(
        private VisitStateService $visitStateService
    ) {}

    public function startExam(Visit $visit): ExamSession
    {
        return DB::transaction(function () use ($visit) {

            if ($visit->current_state !== VisitState::S3->value) {
                throw new Exception('診察開始できない状態です');
            }

            $session = $this->getOrCreateSession($visit);

            // 🔥 Visit状態変更は必ず Service 経由
            $this->visitStateService->transition(
                $visit,
                VisitState::S4->value
            );

            $session->update([
                'current_state' => ExamSession::STATE_IN_EXAM,
                'started_at'    => now(),
                'ai_config_version' => app(AiConfigService::class)->currentVersion(),
            ]);

            return $session;
        });
    }

    public function endExam(Visit $visit): void
    {
        DB::transaction(function () use ($visit) {

            if ($visit->current_state !== VisitState::S4->value) {
                throw new Exception('診察終了できない状態です');
            }

            $session = $visit->examSession;

            if (! $session || ! $session->isState(ExamSession::STATE_IN_EXAM)) {
                throw new Exception('診察セッション不整合');
            }

            // 🔥 Visit状態変更は Service 経由
            $this->visitStateService->transition(
                $visit,
                VisitState::S6->value
            );

            $session->update([
                'current_state' => ExamSession::STATE_FINISHED,
                'ended_at'      => now(),
            ]);
        });
    }

    private function getOrCreateSession(Visit $visit): ExamSession
    {
        return $visit->examSession
            ?? ExamSession::create([
                'visit_id'      => $visit->id,
                'current_state' => ExamSession::STATE_IDLE,
            ]);
    }
}

