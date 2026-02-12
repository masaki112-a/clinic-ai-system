<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptQrRequest;
use App\Http\Requests\AcceptManualRequest;
use App\Http\Requests\CallVisitRequest;
use App\Http\Requests\EnterVisitRequest;
use App\Http\Requests\MarkAbsentRequest;
use App\Http\Requests\VisitListRequest;
use App\Http\Resources\VisitResource;
use App\Http\Resources\VisitCollection;
use App\Models\Visit;
use App\Services\VisitStateService;
use App\Services\VisitCodeGenerator;
use App\Enums\VisitState;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class VisitController extends Controller
{
    public function __construct(
        private VisitStateService $visitStateService,
        private VisitCodeGenerator $visitCodeGenerator
    ) {}

    /**
     * Get visit list with filtering, pagination, and sorting
     */
    public function index(VisitListRequest $request): VisitCollection
    {
        $query = Visit::query();

        // Filter by state(s)
        if ($request->has('state')) {
            $states = array_map('trim', explode(',', $request->input('state')));
            $query->whereIn('current_state', $states);
        }

        // Filter by specific date
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Sorting
        $sortField = $request->input('sort', 'created_at');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $visits = $query->paginate($perPage);

        return new VisitCollection($visits);
    }

    /**
     * Get visit detail with state transition history
     */
    public function show(int $id): JsonResponse
    {
        $visit = Visit::with(['stateLogs' => function ($query) {
            $query->orderBy('created_at', 'asc');
        }])->find($id);

        if (!$visit) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => '指定された来院情報が見つかりません',
                ]
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new VisitResource($visit),
        ]);
    }

    /**
     * QR/IC card acceptance
     */
    public function acceptQr(AcceptQrRequest $request): JsonResponse
    {
        $visitCode = $request->validated()['visit_code'];

        // Find or create visit
        $visit = Visit::firstOrCreate(
            ['visit_code' => $visitCode],
            ['current_state' => VisitState::S0->value]
        );

        // Check if already accepted
        if ($visit->current_state !== VisitState::S0) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_ACCEPTED',
                    'message' => 'この来院コードは既に受付済みです',
                ]
            ], 409);
        }

        // Transition to S1 (accepted)
        try {
            $this->visitStateService->transition($visit, VisitState::S1->value);
            $visit->refresh();

            return response()->json([
                'success' => true,
                'data' => new VisitResource($visit),
            ]);
        } catch (\App\Exceptions\StateTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => $e->getMessage(),
                ]
            ], 409);
        }
    }

    /**
     * Manual acceptance (for patients without QR/IC card)
     */
    public function acceptManual(AcceptManualRequest $request): JsonResponse
    {
        // Generate unique visit code
        $visitCode = $this->visitCodeGenerator->generateManualCode();

        // Create visit with S0 state
        $visit = Visit::create([
            'visit_code' => $visitCode,
            'current_state' => VisitState::S0->value,
        ]);

        // Transition to S1 (accepted)
        try {
            $this->visitStateService->transition($visit, VisitState::S1->value);
            $visit->refresh();

            return response()->json([
                'success' => true,
                'data' => new VisitResource($visit),
            ]);
        } catch (\App\Exceptions\StateTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => $e->getMessage(),
                ]
            ], 409);
        }
    }

    /**
     * Call patient (S2 → S3 transition)
     */
    public function call(int $id, CallVisitRequest $request): JsonResponse
    {
        $visit = Visit::find($id);

        if (!$visit) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => '指定された来院情報が見つかりません',
                ]
            ], 404);
        }

        // Check if current state is S2 (waiting)
        if ($visit->current_state !== VisitState::S2) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => "この来院は呼出できる状態ではありません（現在: {$visit->current_state->value}）",
                ]
            ], 409);
        }

        // Transition to S3 (calling)
        try {
            $this->visitStateService->transition($visit, VisitState::S3->value);
            $visit->refresh();

            return response()->json([
                'success' => true,
                'data' => new VisitResource($visit),
            ]);
        } catch (\App\Exceptions\StateTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => $e->getMessage(),
                ]
            ], 409);
        }
    }

    /**
     * Patient enters examination room (S3 → S4 transition)
     */
    public function enter(int $id, EnterVisitRequest $request): JsonResponse
    {
        $visit = Visit::find($id);

        if (!$visit) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => '指定された来院情報が見つかりません',
                ]
            ], 404);
        }

        // Check if current state is S3 (calling)
        if ($visit->current_state !== VisitState::S3) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => "この来院は入室できる状態ではありません（現在: {$visit->current_state->value}）",
                ]
            ], 409);
        }

        // Transition to S4 (in examination)
        try {
            $this->visitStateService->transition($visit, VisitState::S4->value);
            $visit->refresh();

            return response()->json([
                'success' => true,
                'data' => new VisitResource($visit),
            ]);
        } catch (\App\Exceptions\StateTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => $e->getMessage(),
                ]
            ], 409);
        }
    }

    /**
     * Mark patient as absent (S3 → S5 transition)
     */
    public function markAbsent(int $id, MarkAbsentRequest $request): JsonResponse
    {
        $visit = Visit::find($id);

        if (!$visit) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => '指定された来院情報が見つかりません',
                ]
            ], 404);
        }

        // Check if current state is S3 (calling)
        if ($visit->current_state !== VisitState::S3) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => "この来院は不在マークできる状態ではありません（現在: {$visit->current_state->value}）",
                ]
            ], 409);
        }

        // Transition to S5 (absent)
        try {
            $reason = $request->input('reason');
            $this->visitStateService->transition($visit, VisitState::S5->value, $reason);
            
            $visit->refresh();

            return response()->json([
                'success' => true,
                'data' => new VisitResource($visit),
            ]);
        } catch (\App\Exceptions\StateTransitionException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                    'message' => $e->getMessage(),
                ]
            ], 409);
        }
    }
}
