<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class VisitListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 一覧取得が成功する()
    {
        Visit::factory()->count(3)->create();

        $response = $this->getJson('/api/visits');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'visit_code',
                        'current_state',
                        'accepted_at',
                        'created_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ]
            ]);

        $this->assertEquals(3, $response->json('meta.total'));
    }

    /** @test */
    public function 状態別フィルタリングが動作する()
    {
        Visit::factory()->create(['current_state' => VisitState::S1->value]);
        Visit::factory()->create(['current_state' => VisitState::S2->value]);
        Visit::factory()->create(['current_state' => VisitState::S3->value]);

        $response = $this->getJson('/api/visits?state=S2');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('S2', $response->json('data.0.current_state'));
    }

    /** @test */
    public function 複数状態でフィルタリングできる()
    {
        Visit::factory()->create(['current_state' => VisitState::S1->value]);
        Visit::factory()->create(['current_state' => VisitState::S2->value]);
        Visit::factory()->create(['current_state' => VisitState::S3->value]);

        $response = $this->getJson('/api/visits?state=S1,S2');

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.total'));
    }

    /** @test */
    public function 日付別フィルタリングが動作する()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();

        Visit::factory()->create(['created_at' => $today]);
        Visit::factory()->create(['created_at' => $yesterday]);

        $response = $this->getJson('/api/visits?date=' . $today->format('Y-m-d'));

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
    }

    /** @test */
    public function 日付範囲でフィルタリングできる()
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        $twoDaysAgo = Carbon::today()->subDays(2);

        Visit::factory()->create(['created_at' => $today]);
        Visit::factory()->create(['created_at' => $yesterday]);
        Visit::factory()->create(['created_at' => $twoDaysAgo]);

        $response = $this->getJson(
            '/api/visits?date_from=' . $yesterday->format('Y-m-d') . 
            '&date_to=' . $today->format('Y-m-d')
        );

        $response->assertStatus(200);
        $this->assertEquals(2, $response->json('meta.total'));
    }

    /** @test */
    public function ページネーションが動作する()
    {
        Visit::factory()->count(25)->create();

        // First page
        $response = $this->getJson('/api/visits?per_page=10&page=1');
        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(1, $response->json('meta.current_page'));
        $this->assertEquals(25, $response->json('meta.total'));

        // Second page
        $response = $this->getJson('/api/visits?per_page=10&page=2');
        $response->assertStatus(200);
        $this->assertEquals(10, count($response->json('data')));
        $this->assertEquals(2, $response->json('meta.current_page'));
    }

    /** @test */
    public function ソートが動作する()
    {
        $visit1 = Visit::factory()->create(['visit_code' => 'AAA']);
        $visit2 = Visit::factory()->create(['visit_code' => 'ZZZ']);
        $visit3 = Visit::factory()->create(['visit_code' => 'MMM']);

        // Ascending
        $response = $this->getJson('/api/visits?sort=visit_code&order=asc');
        $response->assertStatus(200);
        $this->assertEquals('AAA', $response->json('data.0.visit_code'));

        // Descending
        $response = $this->getJson('/api/visits?sort=visit_code&order=desc');
        $response->assertStatus(200);
        $this->assertEquals('ZZZ', $response->json('data.0.visit_code'));
    }

    /** @test */
    public function 空の結果を返せる()
    {
        $response = $this->getJson('/api/visits');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
                'meta' => [
                    'total' => 0,
                ]
            ]);
    }

    /** @test */
    public function 無効な状態でフィルタするとエラーになる()
    {
        $response = $this->getJson('/api/visits?state=INVALID');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ]
            ]);
    }

    /** @test */
    public function 無効な日付フォーマットでエラーになる()
    {
        $response = $this->getJson('/api/visits?date=2026/02/12');

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                ]
            ]);
    }

    /** @test */
    public function 複数の条件を組み合わせてフィルタできる()
    {
        $today = Carbon::today();
        
        Visit::factory()->create([
            'current_state' => VisitState::S1->value,
            'created_at' => $today,
        ]);
        Visit::factory()->create([
            'current_state' => VisitState::S2->value,
            'created_at' => $today,
        ]);
        Visit::factory()->create([
            'current_state' => VisitState::S1->value,
            'created_at' => Carbon::yesterday(),
        ]);

        $response = $this->getJson(
            '/api/visits?state=S1&date=' . $today->format('Y-m-d')
        );

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('S1', $response->json('data.0.current_state'));
    }
}
