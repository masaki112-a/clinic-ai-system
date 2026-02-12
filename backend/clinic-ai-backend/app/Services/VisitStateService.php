<?php

namespace App\Services;

use App\Models\Visit;
use App\Models\StateLog;
use Illuminate\Support\Facades\DB;

class VisitStateService
{
    public function transition(Visit $visit, string $toState): void
    {
        $fromState = $visit->current_state;

        if (! VisitStateTransition::can($fromState, $toState)) {
            abort(409, "Invalid visit state transition: {$fromState} → {$toState}");
        }

        DB::transaction(function () use ($visit, $fromState, $toState) {

            $visit->update(array_merge(
                ['current_state' => $toState],
                $this->timestampsFor($toState)
            ));

            StateLog::create([
                'visit_id'   => $visit->id,
                'from_state' => $fromState,
                'to_state'   => $toState,
                'changed_at' => now(),
            ]);
        });
    }

    private function timestampsFor(string $state): array
    {
        return match ($state) {
            VisitState::S1->value => ['accepted_at' => now()],
            VisitState::S2->value => ['called_at' => now()],
            VisitState::S3->value => ['exam_started_at' => now()],
            VisitState::S4->value => ['exam_ended_at' => now()],
            VisitState::S6->value => ['paid_at' => now()],
            default               => [],
        };
    }
}


