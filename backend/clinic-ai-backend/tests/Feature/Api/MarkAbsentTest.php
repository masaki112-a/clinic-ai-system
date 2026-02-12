<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MarkAbsentTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function s3からの不在マークが成功する()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/mark-absent");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $visit->id,
                    'current_state' => 'S5',
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'id' => $visit->id,
            'current_state' => 'S5',
        ]);
    }

    /** @test */
    public function 状態ログが記録される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $this->postJson("/api/visits/{$visit->id}/mark-absent");

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S3',
            'to_state' => 'S5',
        ]);
    }

    /** @test */
    public function reasonを指定した場合状態ログに記録される()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $this->postJson("/api/visits/{$visit->id}/mark-absent", [
            'reason' => '呼び出しに応答なし',
        ]);

        $this->assertDatabaseHas('state_logs', [
            'visit_id' => $visit->id,
            'from_state' => 'S3',
            'to_state' => 'S5',
            'reason' => '呼び出しに応答なし',
        ]);
    }

    /** @test */
    public function s2からの不在マークはエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/mark-absent");

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
    public function s1からの不在マークはエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S1->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/mark-absent");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function s4診察中からの不在マークはエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S4->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/mark-absent");

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_STATE_TRANSITION',
                ]
            ]);
    }

    /** @test */
    public function s5既に不在からの不在マークはエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S5->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/mark-absent");

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
        $response = $this->postJson('/api/visits/999/mark-absent');

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
    public function reasonフィールドを指定できる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        $response = $this->postJson("/api/visits/{$visit->id}/mark-absent", [
            'reason' => 'トイレに行っていた',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function 無効なreasonでエラーになる()
    {
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
        ]);

        // 501 characters (exceeds max 500)
        $longReason = str_repeat('あ', 501);

        $response = $this->postJson("/api/visits/{$visit->id}/mark-absent", [
            'reason' => $longReason,
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
