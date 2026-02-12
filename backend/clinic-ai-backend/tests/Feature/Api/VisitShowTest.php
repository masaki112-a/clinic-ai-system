<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Models\StateLog;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisitShowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 詳細取得が成功する()
    {
        $visit = Visit::factory()->create([
            'visit_code' => 'QR20260212001',
            'current_state' => VisitState::S1->value,
        ]);

        $response = $this->getJson("/api/visits/{$visit->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'visit_code' => 'QR20260212001',
                    'current_state' => 'S1',
                ]
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'visit_code',
                    'current_state',
                    'is_no_exam',
                    'recall_count',
                    'accepted_at',
                    'created_at',
                    'updated_at',
                    'state_logs',
                ]
            ]);
    }

    /** @test */
    public function 状態ログが含まれる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        // Create state logs
        StateLog::factory()->create([
            'visit_id' => $visit->id,
            'from_state' => VisitState::S0->value,
            'to_state' => VisitState::S1->value,
        ]);

        StateLog::factory()->create([
            'visit_id' => $visit->id,
            'from_state' => VisitState::S1->value,
            'to_state' => VisitState::S2->value,
        ]);

        $response = $this->getJson("/api/visits/{$visit->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data.state_logs')
            ->assertJsonStructure([
                'data' => [
                    'state_logs' => [
                        '*' => [
                            'id',
                            'from_state',
                            'to_state',
                            'reason',
                            'created_at',
                        ]
                    ]
                ]
            ]);
    }

    /** @test */
    public function 存在しないidでエラーになる()
    {
        $response = $this->getJson('/api/visits/999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => '指定された来院情報が見つかりません',
                ]
            ]);
    }

    /** @test */
    public function 状態ログが時系列順に並んでいる()
    {
        $visit = Visit::factory()->create();

        // Create logs in reverse order
        $log2 = StateLog::factory()->create([
            'visit_id' => $visit->id,
            'from_state' => VisitState::S1->value,
            'to_state' => VisitState::S2->value,
            'created_at' => now()->addMinutes(10),
        ]);

        $log1 = StateLog::factory()->create([
            'visit_id' => $visit->id,
            'from_state' => VisitState::S0->value,
            'to_state' => VisitState::S1->value,
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/visits/{$visit->id}");

        $response->assertStatus(200);
        
        $logs = $response->json('data.state_logs');
        
        // First log should be S0->S1
        $this->assertEquals('S0', $logs[0]['from_state']);
        $this->assertEquals('S1', $logs[0]['to_state']);
        
        // Second log should be S1->S2
        $this->assertEquals('S1', $logs[1]['from_state']);
        $this->assertEquals('S2', $logs[1]['to_state']);
    }

    /** @test */
    public function 状態ログが空でも正常に動作する()
    {
        $visit = Visit::factory()->create();

        $response = $this->getJson("/api/visits/{$visit->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'state_logs' => [],
                ]
            ]);
    }
}
