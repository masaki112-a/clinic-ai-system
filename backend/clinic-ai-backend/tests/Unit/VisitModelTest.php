<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VisitModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 再呼出可能かチェックできる()
    {
        // 再呼出可能（S3、回数0）
        $visit = Visit::factory()->create([
            'current_state' => VisitState::S3->value,
            'recall_count' => 0,
        ]);
        $this->assertTrue($visit->canRecall());

        // 再呼出可能（S3、回数2）
        $visit->recall_count = 2;
        $this->assertTrue($visit->canRecall());

        // 再呼出不可（S3、回数3）
        $visit->recall_count = 3;
        $this->assertFalse($visit->canRecall());

        // 再呼出不可（S4、回数0）
        $visit->current_state = VisitState::S4->value;
        $visit->recall_count = 0;
        $this->assertFalse($visit->canRecall());
    }

    /** @test */
    public function 診察なし会計かチェックできる()
    {
        // 診察なし会計
        $visit = Visit::factory()->create(['is_no_exam' => true]);
        $this->assertTrue($visit->isNoExam());

        // 通常の診察
        $visit = Visit::factory()->create(['is_no_exam' => false]);
        $this->assertFalse($visit->isNoExam());
    }

    /** @test */
    public function ExamSessionとリレーションがある()
    {
        $visit = Visit::factory()->create();
        
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            $visit->examSession()
        );
    }
}
