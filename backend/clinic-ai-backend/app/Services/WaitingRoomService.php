<?php

namespace App\Services;

use App\Models\Visit;
use App\Enums\VisitState;
use App\Services\VisitStateService;

class WaitingRoomService
{
    /**
     * 現在の待機人数を取得
     */
    public function countWaiting(): int
    {
        return Visit::where('current_state', VisitState::S2->value)
            ->whereNull('ended_at')
            ->count();
    }

    /**
     * 待機中Visit一覧取得（将来拡張用）
     */
    public function getWaitingVisits()
    {
        return Visit::where('current_state', VisitState::S2->value)
            ->whereNull('ended_at')
            ->orderBy('accepted_at')
            ->get();
    }

    public function callNext(VisitStateService $stateService): ?\App\Models\Visit
    {
        $next = \App\Models\Visit::where(
            'current_state',
            VisitState::S2->value
        )
        ->whereNull('ended_at')
        ->orderBy('accepted_at')
        ->first();

        if (!$next) {
            return null;
        }

        $stateService->transition($next, VisitState::S3->value);

        return $next->fresh();
    }
}
