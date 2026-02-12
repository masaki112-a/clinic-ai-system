<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Visit;
use App\Services\VisitStateService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisitStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 正常な状態遷移は成功する()
    {
        $visit = Visit::create([
            'patient_id' => 'TEST001',
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
            'patient_id' => 'TEST002',
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
            'patient_id' => 'TEST003',
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
            'patient_id' => 'TEST004',
            'current_state' => 'S2',
        ]);

        $service = new VisitStateService();
        $service->transition($visit, 'S7');

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S7',
        ]);
    }



}
