<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnterVisitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function s3からの入室が成功する()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/enter");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'current_state' => 'S4',
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S4',
        ]);
    }

    /** @test */
    public function exam_started_atタイムスタンプが設定される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
            'exam_started_at' => null,
        ]);

        $this->postJson("/api/visits/{$visit->id}/enter");

        $visit->refresh();
        $this->assertNotNull($visit->exam_started_at);
    }

    /** @test */
    public function 状態ログが記録される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $this->postJson("/api/visits/{$visit->id}/enter");

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S3',
            'to_state' => 'S4',
        ]);
    }

    /** @test */
    public function s2からの入室はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/enter");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);

        // State should not change
        $visit->refresh();
        $this->assertEquals(VisitState::S2, $visit->current_state);
    }

    /** @test */
    public function s1からの入室はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S1->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/enter");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function s4既に診察中からの入室はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S4->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/enter");

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
        $response = $this->postJson('/api/visits/999/enter');

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
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/enter", [
            'note' => '診察開始',
        ]);

        $response->assertStatus(200);
        
        // Note: note is accepted but not stored in this phase
        // Will be implemented when note/memo feature is added
    }
}
