<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Visit;
use App\Models\StateLog;
use App\Enums\VisitState;
use App\Services\VisitStateService;
use App\Exceptions\StateTransitionException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StateTransitionFlowTest extends TestCase
{
    use RefreshDatabase;

    private VisitStateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VisitStateService::class);
    }

    /** @test */
    public function 正常フロー_S0からS9まで遷移できる()
    {
        $visit = Visit::factory()->create(['current_state' => 'S0']);

        // S0 → S1
        $this->service->transition($visit, 'S1');
        $this->assertEquals('S1', $visit->fresh()->current_state->value);
        $this->assertNotNull($visit->fresh()->accepted_at);

        // S1 → S2
        $this->service->transition($visit, 'S2');
        $this->assertEquals('S2', $visit->fresh()->current_state->value);

        // S2 → S3
        $this->service->transition($visit, 'S3');
        $this->assertEquals('S3', $visit->fresh()->current_state->value);
        $this->assertNotNull($visit->fresh()->called_at);

        // S3 → S4
        $this->service->transition($visit, 'S4');
        $this->assertEquals('S4', $visit->fresh()->current_state->value);
        $this->assertNotNull($visit->fresh()->exam_started_at);

        // S4 → S6
        $this->service->transition($visit, 'S6');
        $this->assertEquals('S6', $visit->fresh()->current_state->value);
        $this->assertNotNull($visit->fresh()->exam_ended_at);

        // S6 → S6.5
        $this->service->transition($visit, 'S6.5');
        $this->assertEquals('S6.5', $visit->fresh()->current_state->value);

        // S6.5 → S7
        $this->service->transition($visit, 'S7');
        $this->assertEquals('S7', $visit->fresh()->current_state->value);

        // S7 → S8
        $this->service->transition($visit, 'S8');
        $this->assertEquals('S8', $visit->fresh()->current_state->value);
        $this->assertNotNull($visit->fresh()->paid_at);

        // S8 → S9
        $this->service->transition($visit, 'S9');
        $this->assertEquals('S9', $visit->fresh()->current_state->value);
        $this->assertNotNull($visit->fresh()->ended_at);

        // StateLogが記録されている
        $this->assertCount(9, StateLog::where('visit_id', $visit->id)->get());
    }

    /** @test */
    public function 例外フロー_再呼出ができる()
    {
        $visit = Visit::factory()->create([
            'current_state' => 'S3',
            'recall_count' => 0,
        ]);

        // S3 → S5 (mark absent - recall_count should NOT increment)
        $this->service->transition($visit, 'S5');
        $this->assertEquals('S5', $visit->fresh()->current_state->value);
        $this->assertEquals(0, $visit->fresh()->recall_count);

        // S5 → S3 (recall - recall_count SHOULD increment)
        $this->service->transition($visit, 'S3');
        $this->assertEquals('S3', $visit->fresh()->current_state->value);
        $this->assertEquals(1, $visit->fresh()->recall_count);
    }

    /** @test */
    public function 例外フロー_診察なし会計ができる()
    {
        $visit = Visit::factory()->create(['current_state' => 'S2']);

        // S2 → S6（理由付き）
        $this->service->transition($visit, 'S6', '処方箋のみ');

        $visit = $visit->fresh();
        $this->assertEquals('S6', $visit->current_state->value);
        $this->assertTrue($visit->is_no_exam);

        // StateLogに理由が記録されている
        $log = StateLog::where('visit_id', $visit->id)
            ->where('to_state', 'S6')
            ->first();
        $this->assertEquals('処方箋のみ', $log->reason);
    }

    /** @test */
    public function 異常系_不正な遷移はエラーになる()
    {
        $visit = Visit::factory()->create(['current_state' => 'S0']);

        $this->expectException(StateTransitionException::class);
        $this->service->transition($visit, 'S9');
    }

    /** @test */
    public function 異常系_診察なし会計で理由なしはエラーになる()
    {
        $visit = Visit::factory()->create(['current_state' => 'S2']);

        $this->expectException(StateTransitionException::class);
        $this->service->transition($visit, 'S6'); // 理由なし
    }
}
