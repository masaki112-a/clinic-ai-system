<?php

namespace App\Services;

use App\Models\UiLock;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class UiLockService
{
    const TTL_MINUTES = 10;

    public function lock(string $uiName, string $lockerId): void
    {
        DB::transaction(function () use ($uiName, $lockerId) {
            $existing = UiLock::where('ui_name', $uiName)->lockForUpdate()->first();

            if ($existing) {
                if (now()->greaterThan($existing->expires_at)) {
                    $existing->delete();
                } else {
                    throw new Exception("UI {$uiName} is already locked");
                }
            }

            UiLock::create([
                'ui_name'     => $uiName,
                'locked_by'   => $lockerId,
                'locked_at'   => now(),
                'expires_at'  => now()->addMinutes(self::TTL_MINUTES),
            ]);
        });
    }

    public function unlock(string $uiName): void
    {
        UiLock::where('ui_name', $uiName)->delete();
    }

    public function forceUnlock(string $uiName, string $operator, string $reason)
    {
        if (empty($reason)) {
            throw new \Exception('解除理由は必須です');
        }

        DB::transaction(function () use ($uiName, $operator, $reason) {
            $lock = DB::table('ui_locks')
                ->where('ui_name', $uiName)
                ->first();

            if (!$lock) {
                throw new \Exception('ロックされていないUIです');
            }

            DB::table('ui_locks')
                ->where('ui_name', $uiName)
                ->delete();

            DB::table('ui_lock_logs')->insert([
                'ui_name'    => $uiName,
                'action'     => 'force_unlock',
                'operator'   => $operator,
                'reason'     => $reason,
                'created_at'=> now(),
            ]);
        });
    }

    public static function assertLocked(string $uiName, string $lockedBy): void
{
    $lock = UiLock::where('ui_name', $uiName)
        ->where('locked_by', $lockedBy)
        ->first();

    if (! $lock) {
        throw new \Exception('UI is not locked by this terminal.');
    }
}

}

