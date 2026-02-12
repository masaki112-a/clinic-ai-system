<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AcceptQrTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function qr受付が成功する()
    {
        $response = $this->postJson('/api/visits/accept/qr', [
            'visit_code' => 'QR20260212001',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'visit_code' => 'QR20260212001',
                    'current_state' => 'S1',
                ]
            ]);

        $this->assertDatabaseHas('visits', [
            'visit_code' => 'QR20260212001',
            'current_state' => 'S1',
        ]);

        $this->assertDatabaseHas('state_logs', [
            'from_state' => 'S0',
            'to_state' => 'S1',
        ]);
    }

    /** @test */
    public function 来院コードなしはエラーになる()
    {
        $response = $this->postJson('/api/visits/accept/qr', []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ]
            ]);
    }

    /** @test */
    public function 既に受付済みの場合はエラーになる()
    {
        // 既にS1の状態で作成
        Visit::factory()->create([
            'visit_code' => 'QR20260212001',
            'current_state' => VisitState::S1->value,
        ]);

        $response = $this->postJson('/api/visits/accept/qr', [
            'visit_code' => 'QR20260212001',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_ACCEPTED',
                ]
            ]);
    }

    /** @test */
    public function s0以外の状態からは受付できない()
    {
        // S2（待機中）の状態で作成
        Visit::factory()->create([
            'visit_code' => 'QR20260212002',
            'current_state' => VisitState::S2->value,
        ]);

        $response = $this->postJson('/api/visits/accept/qr', [
            'visit_code' => 'QR20260212002',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_ACCEPTED',
                ]
            ]);
    }

    /** @test */
    public function 同じコードで複数回受付しようとするとエラーになる()
    {
        // 1回目は成功
        $this->postJson('/api/visits/accept/qr', [
            'visit_code' => 'QR20260212003',
        ])->assertStatus(200);

        // 2回目はエラー
        $response = $this->postJson('/api/visits/accept/qr', [
            'visit_code' => 'QR20260212003',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'ALREADY_ACCEPTED',
                ]
            ]);
    }
}
