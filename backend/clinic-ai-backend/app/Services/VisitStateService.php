<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\StateLog;
use App\Enums\VisitState;
use Illuminate\Support\Facades\DB;

class VisitStateService
{
    public function transition(Visit $visit, string $toState, ?string $reason = null): void
    {
        $fromState = $visit->current_state;

        // 遷移チェック
        if (! VisitStateTransition::can($fromState, $toState)) {
            abort(409, "Invalid visit state transition: {$fromState} → {$toState}");
        }

        // 理由必須チェック
        if (VisitStateTransition::requiresReason($fromState, $toState) && empty($reason)) {
            abort(400, "Reason is required for this transition: {$fromState} → {$toState}");
        }

        DB::transaction(function () use ($visit, $fromState, $toState, $reason) {

            $updateData = array_merge(
                ['current_state' => $toState],
                $this->timestampsFor($toState),
                $this->additionalFieldsFor($fromState, $toState)
            );

            $visit->update($updateData);

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
            VisitState::S3->value => ['called_at' => now()],
            VisitState::S4->value => ['exam_started_at' => now()],
            VisitState::S6->value => ['exam_ended_at' => now()],
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

        // S3→S5（再呼出）
        if ($from === VisitState::S3->value && $to === VisitState::S5->value) {
            $fields['recall_count'] = DB::raw('recall_count + 1');
        }

        // S2→S7（診察なし会計）
        if ($from === VisitState::S2->value && $to === VisitState::S7->value) {
            $fields['is_no_exam'] = true;
        }

        return $fields;
    }
}


