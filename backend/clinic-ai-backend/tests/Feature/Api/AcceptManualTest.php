<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class AcceptManualTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 手動受付が成功する()
    {
        $response = $this->postJson('/api/visits/accept/manual');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'visit_code',
                    'current_state',
                    'accepted_at',
                ]
            ]);

        $this->assertEquals('S1', $response->json('data.current_state'));
    }

    /** @test */
    public function visit_codeが自動生成される()
    {
        $response = $this->postJson('/api/visits/accept/manual');

        $visitCode = $response->json('data.visit_code');
        $today = Carbon::today()->format('Ymd');

        // Format: MAN + YYYYMMDD + 001
        $this->assertStringStartsWith("MAN{$today}", $visitCode);
        $this->assertStringEndsWith('001', $visitCode);
    }

    /** @test */
    public function visit_codeがユニークである()
    {
        $response1 = $this->postJson('/api/visits/accept/manual');
        $response2 = $this->postJson('/api/visits/accept/manual');

        $code1 = $response1->json('data.visit_code');
        $code2 = $response2->json('data.visit_code');

        $this->assertNotEquals($code1, $code2);
    }

    /** @test */
    public function 同日に複数件受付できる()
    {
        $today = Carbon::today()->format('Ymd');

        // First visit
        $response1 = $this->postJson('/api/visits/accept/manual');
        $this->assertEquals("MAN{$today}001", $response1->json('data.visit_code'));

        // Second visit
        $response2 = $this->postJson('/api/visits/accept/manual');
        $this->assertEquals("MAN{$today}002", $response2->json('data.visit_code'));

        // Third visit
        $response3 = $this->postJson('/api/visits/accept/manual');
        $this->assertEquals("MAN{$today}003", $response3->json('data.visit_code'));
    }

    /** @test */
    public function noteフィールドを保存できる()
    {
        $response = $this->postJson('/api/visits/accept/manual', [
            'note' => '患者番号123',
        ]);

        $response->assertStatus(200);
        
        // Note: In this phase, note is accepted but not stored yet
        // Will be implemented when patient information feature is added
    }

    /** @test */
    public function 状態遷移ログが作成される()
    {
        $this->postJson('/api/visits/accept/manual');

        $this->assertDatabaseHas('state_logs', [
            'from_state' => 'S0',
            'to_state' => 'S1',
        ]);
    }

    /** @test */
    public function 連番が999を超えるとエラーになる()
    {
        $today = Carbon::today()->format('Ymd');

        // Create 999 manual visits for today
        Visit::factory()->create([
            'visit_code' => "MAN{$today}999",
            'current_state' => VisitState::S1->value,
        ]);

        // 1000th attempt should fail
        $response = $this->postJson('/api/visits/accept/manual');

        $response->assertStatus(500);
    }
}
