<?php

namespace App\Http\Controllers;

use App\Services\UiLockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UiExitController extends Controller
{
    public function exit(Request $request, UiLockService $lockService): RedirectResponse
    {
        $uiName   = $request->input('ui_name');
        $lockerId = $request->ip(); // launch と一致させる

        // 自分が取ったロックか確認
        $lockService::assertLocked($uiName, $lockerId);

        // ロック解除
        $lockService->unlock($uiName);

        return redirect()->route('home');
    }
}
