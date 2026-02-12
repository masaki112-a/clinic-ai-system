<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VisitCodeGenerator;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class VisitCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private VisitCodeGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new VisitCodeGenerator();
    }

    /** @test */
    public function 最初の手動受付コードは001で終わる()
    {
        $code = $this->generator->generateManualCode();
        $today = Carbon::today()->format('Ymd');

        $this->assertEquals("MAN{$today}001", $code);
    }

    /** @test */
    public function 既存の手動受付がある場合は連番が増える()
    {
        $today = Carbon::today()->format('Ymd');

        // Create existing manual visit
        Visit::factory()->create([
            'visit_code' => "MAN{$today}005",
            'current_state' => VisitState::S1->value,
        ]);

        $code = $this->generator->generateManualCode();

        $this->assertEquals("MAN{$today}006", $code);
    }

    /** @test */
    public function 複数回呼び出すと連番が増える()
    {
        $today = Carbon::today()->format('Ymd');

        $code1 = $this->generator->generateManualCode();
        Visit::factory()->create([
            'visit_code' => $code1,
            'current_state' => VisitState::S1->value,
        ]);

        $code2 = $this->generator->generateManualCode();
        Visit::factory()->create([
            'visit_code' => $code2,
            'current_state' => VisitState::S1->value,
        ]);

        $this->assertEquals("MAN{$today}001", $code1);
        $this->assertEquals("MAN{$today}002", $code2);
    }
}
