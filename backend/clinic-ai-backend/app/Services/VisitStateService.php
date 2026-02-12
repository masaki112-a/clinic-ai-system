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
        $fromState = $visit->current_state instanceof VisitState 
            ? $visit->current_state->value 
            : $visit->current_state;

        if (! VisitStateTransition::can($fromState, $toState)) {
            throw new StateTransitionException("Invalid visit state transition: {$fromState} → {$toState}");
        }

        // S2 → S7 は理由必須
        if (VisitStateTransition::requiresReason($fromState, $toState) && empty($reason)) {
            abort(422, 'Reason is required for no-exam billing transition');
        }

        DB::transaction(function () use ($visit, $fromState, $toState, $reason) {

            $updates = array_merge(
                ['current_state' => $toState],
                $this->timestampsFor($toState),
                $this->fieldsFor($fromState, $toState)
            );

            $visit->update($updates);

            StateLog::create([
                'visit_id'   => $visit->id,
                'from_state' => $fromState,
                'to_state'   => $toState,
                'reason'     => $reason,
                'changed_at' => now(),
            ]);
        });
    }

    private function timestampsFor(string $state): array
    {
        return match ($state) {
            'S1' => ['accepted_at' => now()],
            'S3' => ['called_at' => now()],
            'S4' => ['exam_started_at' => now()],
            'S6' => ['exam_ended_at' => now()],
            'S8' => ['paid_at' => now()],
            'S9' => ['ended_at' => now()],
            default => [],
        };
    }

    private function fieldsFor(string $from, string $to): array
    {
        $fields = [];

        // S2 → S7: 診察なし会計
        if ($from === 'S2' && $to === 'S7') {
            $fields['is_no_exam'] = true;
        }

        // S3 → S5: 再呼出カウント増加
        if ($from === 'S3' && $to === 'S5') {
            $fields['recall_count'] = DB::raw('recall_count + 1');
        }

        return $fields;
    }
}


