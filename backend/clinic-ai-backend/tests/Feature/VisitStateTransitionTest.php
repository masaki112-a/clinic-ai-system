<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Visit;
use App\Services\VisitStateService;
use App\Exceptions\StateTransitionException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisitStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 正常な状態遷移は成功する()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST001',
            'current_state' => 'S0',
        ]);

        $service = new VisitStateService();
        $service->transition($visit, 'S1');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S1',
        ]);
    }

    /** @test */
    public function 状態遷移時にログが必ず作成される()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST002',
            'current_state' => 'S1',
        ]);

        $service = new VisitStateService();
        $service->transition($visit, 'S2');

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S1',
            'to_state' => 'S2',
        ]);
    }

    /** @test */
    public function 不正な状態遷移は例外になる()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST003',
            'current_state' => 'S0',
        ]);

        $service = new VisitStateService();

        $this->expectException(\Exception::class);

        $service->transition($visit, 'S3');
    }

    /** @test */
    public function 診察なし会計への例外遷移は許可される()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST004',
            'current_state' => 'S2',
        ]);

        $service = new VisitStateService();
        $service->transition($visit, 'S6', '処方箋のみ');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S6',
            'is_no_exam' => true,
        ]);
    }

    /** @test */
    public function 診察なし会計への遷移は理由が必須()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST005',
            'current_state' => 'S2',
        ]);

        $service = new VisitStateService();

        $this->expectException(StateTransitionException::class);

        $service->transition($visit, 'S6');
    }

    /** @test */
    public function S3からS5への再呼出遷移が成功する()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST006',
            'current_state' => 'S3',
            'recall_count' => 0,
        ]);

        $service = new VisitStateService();
        $service->transition($visit, 'S5');

        $visit->refresh();

        $this->assertEquals('S5', $visit->current_state->value);
        $this->assertEquals(0, $visit->recall_count); // Mark absent does NOT increment
    }

    /** @test */
    public function S5からS3への遷移が成功する()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST007',
            'current_state' => 'S5',
            'recall_count' => 0,
        ]);

        $service = new VisitStateService();
        $service->transition($visit, 'S3');

        $visit->refresh();

        $this->assertEquals('S3', $visit->current_state->value);
        $this->assertEquals(1, $visit->recall_count); // Recall DOES increment
    }

    /** @test */
    public function canRecallメソッドが正しく動作する()
    {
        $visit1 = Visit::create([
            'visit_code' => 'TEST008',
            'current_state' => 'S3',
            'recall_count' => 0,
        ]);

        $visit2 = Visit::create([
            'visit_code' => 'TEST009',
            'current_state' => 'S3',
            'recall_count' => 3,
        ]);

        $visit3 = Visit::create([
            'visit_code' => 'TEST010',
            'current_state' => 'S4',
            'recall_count' => 0,
        ]);

        $this->assertTrue($visit1->canRecall());
        $this->assertFalse($visit2->canRecall()); // 最大回数到達
        $this->assertFalse($visit3->canRecall()); // 状態が違う
    }

    /** @test */
    public function 完了時にended_atが記録される()
    {
        $visit = Visit::create([
            'visit_code' => 'TEST011',
            'current_state' => 'S8',
        ]);

        $service = new VisitStateService();
        $service->transition($visit, 'S9');

        $visit->refresh();

        $this->assertEquals('S9', $visit->current_state->value);
        $this->assertNotNull($visit->ended_at);
    }



}
