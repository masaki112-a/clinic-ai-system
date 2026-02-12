<?php

namespace App\Http\Controllers;

use App\Models\UiLock;
use Illuminate\View\View;
use App\Services\UiLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index(): View
    {
        // UI定義を取得
        $uis = config('ui');

        // 現在のロック一覧を取得
        $locks = UiLock::all()->keyBy('ui_name');

        // 表示用に整形
        $uiStatus = [];

        foreach ($uis as $uiName => $uiConfig) {
            $lock = $locks->get($uiName);

            $remainingSeconds = null;

            if ($lock && $lock->expires_at) {
                $remainingSeconds = Carbon::now()->diffInSeconds(
                    Carbon::parse($lock->expires_at),
                    false // 期限切れはマイナス
                );
            }

            $uiStatus[] = [
                'name'        => $uiName,
                'label'       => $uiConfig['label'],
                'lockable'    => $uiConfig['lockable'],
                'is_locked'   => (bool) $lock,
                'locked_by'   => $lock?->locked_by,
                'expires_at'  => $lock?->expires_at,
                'remaining'   => $remainingSeconds,
            ];
        }

        return view('home.index', [
            'uiStatus' => $uiStatus,
        ]);
    }

    public function launch(Request $request, UiLockService $lockService): RedirectResponse
    {
        $uiName   = $request->input('ui_name');
        $lockerId = $request->ip(); // ← 今はIPでOK（後で端末IDにする）

        $uiConfig = config("ui.$uiName");

        if (! $uiConfig) {
            abort(404);
        }

        // ロック不要UIはそのまま遷移
        if (! $uiConfig['lockable']) {
            return redirect()->route($uiName);
        }

        try {
            $lockService->lock($uiName, $lockerId);
        } catch (\Exception $e) {
            return redirect()->route('home')
                ->with('error', 'この画面は現在使用中です');
        }

        return redirect()->route($uiName);
    }

}


