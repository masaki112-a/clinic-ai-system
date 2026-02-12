<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptQrRequest;
use App\Http\Requests\AcceptManualRequest;
use App\Http\Resources\VisitResource;
use App\Models\Visit;
use App\Services\VisitStateService;
use App\Services\VisitCodeGenerator;
use App\Enums\VisitState;
use Illuminate\Http\JsonResponse;

class VisitController extends Controller
{
    public function __construct(
        private VisitStateService $visitStateService,
        private VisitCodeGenerator $visitCodeGenerator
    ) {}

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
}
