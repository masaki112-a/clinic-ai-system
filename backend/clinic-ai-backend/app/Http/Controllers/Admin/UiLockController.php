<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UiLock;
use App\Services\UiLockService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UiLockController extends Controller
{
    public function index(): View
    {
        return view('admin.locks', [
            'locks' => UiLock::all(),
        ]);
    }

    public function forceUnlock(
        Request $request,
        UiLockService $lockService
    ): RedirectResponse {
        $request->validate([
            'ui_name' => 'required|string',
            'reason'  => 'required|string|min:5',
        ]);

        // operator は今はIPでOK
        $operator = $request->ip();

        $lockService->forceUnlock(
            $request->ui_name,
            $operator,
            $request->reason
        );

        return redirect()
            ->route('admin.locks')
            ->with('success', '強制解除しました');
    }
}
