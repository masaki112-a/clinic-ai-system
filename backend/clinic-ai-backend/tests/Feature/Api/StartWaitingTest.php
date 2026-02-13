<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class StartWaitingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function s1からの待機開始が成功する()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S1->value,
            'waiting_started_at' => null,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/start-waiting");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'current_state' => 'S2',
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S2',
        ]);
    }

    /** @test */
    public function waiting_started_atタイムスタンプが設定される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S1->value,
            'waiting_started_at' => null,
        ]);

        $this->postJson("/api/visits/{$visit->id}/start-waiting");

        $visit->refresh();
        $this->assertNotNull($visit->waiting_started_at);
    }

    /** @test */
    public function 状態ログが記録される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S1->value,
        ]);

        $this->postJson("/api/visits/{$visit->id}/start-waiting");

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S1',
            'to_state' => 'S2',
        ]);
    }

    /** @test */
    public function s0からの待機開始はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S0->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/start-waiting");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);

        // State should not change
        $visit->refresh();
        $this->assertEquals('S0', $visit->current_state->value);
    }

    /** @test */
    public function s2既に待機中からの待機開始はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/start-waiting");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function s3以降からの待機開始はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/start-waiting");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function 存在しないidでエラーになる()
    {
        $response = $this->postJson('/api/visits/999/start-waiting');

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
    public function noteフィールドを指定できる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S1->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/start-waiting", [
            'note' => '待合室へ案内',
        ]);

        $response->assertStatus(200);
    }
}
