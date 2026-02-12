<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visit;
use App\Services\VisitStateService;
use App\Enums\VisitState;

class ReceptionController extends Controller
{
    /**
     * 受付（S0 → S1）
     */
    public function accept(Request $request, VisitStateService $service)
    {
        $request->validate([
            'visit_code' => 'required|string|max:255',
        ]);

        $visit = Visit::firstOrCreate(
            [
                'visit_code' => $request->visit_code,
                'ended_at'   => null,
            ],
            [
                'current_state' => VisitState::S1->value,
                'accepted_at'   => now(),
            ]
        );

        // 既存Visitがある場合でも、状態保証はServiceに任せる
        $service->transition($visit, VisitState::S1->value);

        return redirect()->back()->with('status', '受付処理完了');
    }

    /**
     * 待機投入（S1 → S2）
     */
    public function enqueue(Visit $visit, VisitStateService $service)
    {
        $service->transition($visit, VisitState::S2->value);

        return redirect()->back()->with('status', '待機に入りました');
    }
}


