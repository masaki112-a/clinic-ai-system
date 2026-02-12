<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use App\ViewModels\WaitingRoomViewModel;
use App\Services\WaitingRoomService;
use App\Services\VisitStateService;
use Illuminate\Http\RedirectResponse;

class WaitingRoomController extends Controller
{
    public function index(WaitingRoomService $service): View
    {
        return view('waiting.index', [
            'vm' => new WaitingRoomViewModel($service),
        ]);
    }

    
    public function callNext(
        WaitingRoomService $waitingService,
        VisitStateService $stateService
    ): RedirectResponse {

        $visit = $waitingService->callNext($stateService);

        if (!$visit) {
            return redirect()->back()
                ->with('status', '呼び出せる患者がいません');
        }

        return redirect()->back()
            ->with('status', "呼出：{$visit->visit_code}");
    }

}
