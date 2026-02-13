<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'visit_code' => $this->visit_code,
            'current_state' => $this->current_state->value,
            'is_no_exam' => $this->is_no_exam,
            'recall_count' => $this->recall_count,
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'waiting_started_at' => $this->waiting_started_at?->toIso8601String(),
            'called_at' => $this->called_at?->toIso8601String(),
            'exam_started_at' => $this->exam_started_at?->toIso8601String(),
            'exam_ended_at' => $this->exam_ended_at?->toIso8601String(),
            'payment_amount' => $this->payment_amount,
            'insurance_type' => $this->insurance_type,
            'payment_ready_at' => $this->payment_ready_at?->toIso8601String(),
            'payment_called_at' => $this->payment_called_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            // Include state logs when loaded (for detail view)
            'state_logs' => StateLogResource::collection($this->whenLoaded('stateLogs')),
        ];
    }
}
