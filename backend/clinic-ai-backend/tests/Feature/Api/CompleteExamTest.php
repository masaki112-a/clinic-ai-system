<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompleteExamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function s4からの診察終了が成功する()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S4->value,
            'exam_started_at' => now()->subMinutes(30),
            'exam_ended_at' => null,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/complete-exam");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'current_state' => 'S6',
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S6',
        ]);
    }

    /** @test */
    public function exam_ended_atタイムスタンプが設定される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S4->value,
            'exam_started_at' => now()->subMinutes(30),
            'exam_ended_at' => null,
        ]);

        $this->postJson("/api/visits/{$visit->id}/complete-exam");

        $visit->refresh();
        $this->assertNotNull($visit->exam_ended_at);
        $this->assertTrue($visit->exam_ended_at->isAfter($visit->exam_started_at));
    }

    /** @test */
    public function 状態ログが記録される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S4->value,
            'exam_started_at' => now()->subMinutes(30),
        ]);

        $this->postJson("/api/visits/{$visit->id}/complete-exam");

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S4',
            'to_state' => 'S6',
        ]);
    }

    /** @test */
    public function s3からの診察終了はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/complete-exam");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);

        // State should not change
        $visit->refresh();
        $this->assertEquals(VisitState::S3, $visit->current_state);
    }

    /** @test */
    public function s2からの診察終了はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/complete-exam");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function s6既に診察終了からの診察終了はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S6->value,
            'exam_ended_at' => now()->subMinutes(10),
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/complete-exam");

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
        $response = $this->postJson('/api/visits/999/complete-exam');

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
            'current_state' => VisitState::S4->value,
            'exam_started_at' => now()->subMinutes(30),
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/complete-exam", [
            'note' => '診察終了、会計へ',
        ]);

        $response->assertStatus(200);
    }
}
