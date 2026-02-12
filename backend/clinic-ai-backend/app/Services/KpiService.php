<?php

namespace App\Services;

use App\Models\StateLog;
use Illuminate\Support\Facades\DB;

class KpiService
{
    public function averageWaitingTime(): ?int
    {
        return StateLog::select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, a.changed_at, b.changed_at)) as avg_seconds'))
            ->from('state_logs as a')
            ->join('state_logs as b', function ($join) {
                $join->on('a.visit_id', '=', 'b.visit_id')
                     ->where('a.to_state', 'accepted')
                     ->where('b.to_state', 'in_exam');
            })
            ->value('avg_seconds');
    }
}
