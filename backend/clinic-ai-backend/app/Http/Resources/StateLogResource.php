<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StateLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_state' => $this->from_state,
            'to_state' => $this->to_state,
            'reason' => $this->reason,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
