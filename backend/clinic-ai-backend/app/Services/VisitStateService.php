<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\StateLog;
use App\Enums\VisitState;
use App\Exceptions\StateTransitionException;
use Illuminate\Support\Facades\DB;

class VisitStateService
{
    public function transition(Visit $visit, string $toState, ?string $reason = null): void
    {
        $fromState = $visit->current_state->value;

        // 遷移チェック
        if (! VisitStateTransition::can($fromState, $toState)) {
            throw new StateTransitionException(
                "Invalid visit state transition: {$fromState} → {$toState}",
                409
            );
        }

        // 理由必須チェック
        if (VisitStateTransition::requiresReason($fromState, $toState) && empty($reason)) {
            throw new StateTransitionException(
                "Reason is required for this transition: {$fromState} → {$toState}",
                400
            );
        }

        DB::transaction(function () use ($visit, $fromState, $toState, $reason) {

            $updateData = array_merge(
                ['current_state' => $toState],
                $this->timestampsFor($toState),
                $this->additionalFieldsFor($fromState, $toState)
            );

            $visit->update($updateData);
            
            // 特殊遷移の追加処理
            $this->postTransitionUpdate($visit, $fromState, $toState);

            StateLog::create([
                'visit_id'   => $visit->id,
                'from_state' => $fromState,
                'to_state'   => $toState,
                'changed_at' => now(),
                'reason'     => $reason,
            ]);
        });
    }

    /**
     * 状態ごとのタイムスタンプ設定
     */
    private function timestampsFor(string $state): array
    {
        return match ($state) {
            VisitState::S1->value => ['accepted_at' => now()],
            VisitState::S2->value => ['waiting_started_at' => now()],
            VisitState::S3->value => ['called_at' => now()],
            VisitState::S4->value => ['exam_started_at' => now()],
            VisitState::S6->value => ['exam_ended_at' => now()],
            VisitState::S6_5->value => ['payment_ready_at' => now()],
            VisitState::S7->value => ['payment_called_at' => now()],
            VisitState::S8->value => ['paid_at' => now()],
            VisitState::S9->value => ['ended_at' => now()],
            default               => [],
        };
    }

    /**
     * 特殊遷移時の追加フィールド設定
     */
    private function additionalFieldsFor(string $from, string $to): array
    {
        $fields = [];

        // S3→S5（再呼出）の場合はDB::raw()は使わず、後でインクリメント
        // S2→S6（診察なし会計）
        if ($from === VisitState::S2->value && $to === VisitState::S6->value) {
            $fields['is_no_exam'] = true;
        }

        return $fields;
    }
    
    /**
     * 特殊遷移後の追加処理
     */
    private function postTransitionUpdate(Visit $visit, string $from, string $to): void
    {
        // S5→S3（再呼出）はインクリメント
        if ($from === VisitState::S5->value && $to === VisitState::S3->value) {
            $visit->increment('recall_count');
        }
    }
}


