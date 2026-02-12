<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CallVisitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function s2からの呼出が成功する()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/call");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'current_state' => 'S3',
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S3',
        ]);
    }

    /** @test */
    public function called_atタイムスタンプが設定される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
            'called_at' => null,
        ]);

        $this->postJson("/api/visits/{$visit->id}/call");

        $visit->refresh();
        $this->assertNotNull($visit->called_at);
    }

    /** @test */
    public function 状態ログが記録される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $this->postJson("/api/visits/{$visit->id}/call");

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S2',
            'to_state' => 'S3',
        ]);
    }

    /** @test */
    public function s1からの呼出はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S1->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/call");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);

        // State should not change
        $visit->refresh();
        $this->assertEquals(VisitState::S1, $visit->current_state);
    }

    /** @test */
    public function s3既に呼出中からの呼出はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/call");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function s4診察中からの呼出はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S4->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/call");

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
        $response = $this->postJson('/api/visits/999/call');

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
    public function room_numberを指定できる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/call", [
            'room_number' => '1',
        ]);

        $response->assertStatus(200);
        
        // Note: room_number is accepted but not stored in this phase
        // Will be implemented when room management feature is added
    }

    /** @test */
    public function 無効なroom_numberでエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/call", [
            'room_number' => '12345678901', // 11 characters (exceeds max 10)
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ]
            ]);
    }
}
