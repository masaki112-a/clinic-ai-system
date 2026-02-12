<?php

namespace App\Services;

use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class VisitCodeGenerator
{
    /**
     * Generate unique manual visit code
     * Format: MAN + YYYYMMDD + 001-999
     */
    public function generateManualCode(): string
    {
        return DB::transaction(function () {
            $today = Carbon::today()->format('Ymd');
            $prefix = "MAN{$today}";

            // Find the highest sequential number for today with row lock
            $latestVisit = Visit::where('visit_code', 'like', "{$prefix}%")
                ->orderBy('visit_code', 'desc')
                ->lockForUpdate()
                ->first();

            if (!$latestVisit) {
                // First manual visit of the day
                $sequence = 1;
            } else {
                // Extract sequence number and increment
                $lastCode = $latestVisit->visit_code;
                $sequence = (int) substr($lastCode, -3) + 1;
            }

            // Ensure sequence doesn't exceed 999
            if ($sequence > 999) {
                throw new \RuntimeException('Daily manual acceptance limit (999) exceeded');
            }

            return $prefix . str_pad($sequence, 3, '0', STR_PAD_LEFT);
        });
    }
}
