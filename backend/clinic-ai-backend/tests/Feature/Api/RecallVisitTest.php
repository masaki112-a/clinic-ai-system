<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RecallVisitTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function s5からの再呼出が成功する()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S5->value,
            'recall_count' => 0,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/recall");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'current_state' => 'S3',
                    'recall_count' => 1,
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S3',
            'recall_count' => 1,
        ]);
    }

    /** @test */
    public function recall_countがインクリメントされる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S5->value,
            'recall_count' => 0,
        ]);

        $this->postJson("/api/visits/{$visit->id}/recall");

        $visit->refresh();
        $this->assertEquals(1, $visit->recall_count);
    }

    /** @test */
    public function called_atタイムスタンプが更新される()
    {
        $oldCalledAt = now()->subMinutes(10);
        
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S5->value,
            'called_at' => $oldCalledAt,
        ]);

        $this->postJson("/api/visits/{$visit->id}/recall");

        $visit->refresh();
        $this->assertNotNull($visit->called_at);
        $this->assertTrue($visit->called_at->isAfter($oldCalledAt));
    }

    /** @test */
    public function 状態ログが記録される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S5->value,
        ]);

        $this->postJson("/api/visits/{$visit->id}/recall");

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S5',
            'to_state' => 'S3',
        ]);
    }

    /** @test */
    public function s3からの再呼出はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/recall");

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
    public function s2からの再呼出はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/recall");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function s4からの再呼出はエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S4->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/recall");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function 複数回再呼出できる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S5->value,
            'recall_count' => 2,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/recall");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'recall_count' => 3,
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'recall_count' => 3,
        ]);
    }

    /** @test */
    public function 存在しないidでエラーになる()
    {
        $response = $this->postJson('/api/visits/999/recall');

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
            'current_state' => VisitState::S5->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/recall", [
            'note' => '2回目の呼出',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function 無効なnoteでエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S5->value,
        ]);

        // 501 characters (exceeds max 500)
        $longNote = str_repeat('あ', 501);

        $response = $this->postJson("/api/visits/{$visit->id}/recall", [
            'note' => $longNote,
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
