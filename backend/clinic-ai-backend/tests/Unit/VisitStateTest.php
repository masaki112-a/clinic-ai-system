<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Enums\VisitState;

class VisitStateTest extends TestCase
{
    /** @test */
    public function 全ての状態が定義されている()
    {
        $this->assertEquals('S0', VisitState::S0->value);
        $this->assertEquals('S1', VisitState::S1->value);
        $this->assertEquals('S2', VisitState::S2->value);
        $this->assertEquals('S3', VisitState::S3->value);
        $this->assertEquals('S4', VisitState::S4->value);
        $this->assertEquals('S5', VisitState::S5->value);
        $this->assertEquals('S6', VisitState::S6->value);
        $this->assertEquals('S6.5', VisitState::S6_5->value);
        $this->assertEquals('S7', VisitState::S7->value);
        $this->assertEquals('S8', VisitState::S8->value);
        $this->assertEquals('S9', VisitState::S9->value);
    }

    /** @test */
    public function 正常な遷移が許可されている()
    {
        // S0 → S1
        $this->assertTrue(VisitState::S0->canTransitionTo(VisitState::S1));

        // S1 → S2
        $this->assertTrue(VisitState::S1->canTransitionTo(VisitState::S2));

        // S2 → S3
        $this->assertTrue(VisitState::S2->canTransitionTo(VisitState::S3));

        // S3 → S4
        $this->assertTrue(VisitState::S3->canTransitionTo(VisitState::S4));

        // S4 → S6
        $this->assertTrue(VisitState::S4->canTransitionTo(VisitState::S6));

        // S6 → S6.5
        $this->assertTrue(VisitState::S6->canTransitionTo(VisitState::S6_5));

        // S6.5 → S7
        $this->assertTrue(VisitState::S6_5->canTransitionTo(VisitState::S7));

        // S7 → S8
        $this->assertTrue(VisitState::S7->canTransitionTo(VisitState::S8));

        // S8 → S9
        $this->assertTrue(VisitState::S8->canTransitionTo(VisitState::S9));
    }

    /** @test */
    public function 例外遷移が許可されている()
    {
        // S2 → S6（診察なし会計）
        $this->assertTrue(VisitState::S2->canTransitionTo(VisitState::S6));

        // S3 → S5（再呼出）
        $this->assertTrue(VisitState::S3->canTransitionTo(VisitState::S5));

        // S5 → S3（再呼出から戻る）
        $this->assertTrue(VisitState::S5->canTransitionTo(VisitState::S3));
    }

    /** @test */
    public function 不正な遷移が拒否される()
    {
        // S0 → S9（スキップ不可）
        $this->assertFalse(VisitState::S0->canTransitionTo(VisitState::S9));

        // S2 → S4（呼出なしで診察開始不可）
        $this->assertFalse(VisitState::S2->canTransitionTo(VisitState::S4));

        // S4 → S2（後退不可）
        $this->assertFalse(VisitState::S4->canTransitionTo(VisitState::S2));

        // S9 → *（終端状態からの遷移不可）
        $this->assertFalse(VisitState::S9->canTransitionTo(VisitState::S0));
    }

    /** @test */
    public function ラベルが正しく取得できる()
    {
        $this->assertEquals('未受付', VisitState::S0->label());
        $this->assertEquals('受付済', VisitState::S1->label());
        $this->assertEquals('待機中', VisitState::S2->label());
        $this->assertEquals('呼出中', VisitState::S3->label());
        $this->assertEquals('診察中', VisitState::S4->label());
        $this->assertEquals('再呼出', VisitState::S5->label());
        $this->assertEquals('会計準備中', VisitState::S6->label());
        $this->assertEquals('会計呼出可', VisitState::S6_5->label());
        $this->assertEquals('会計中', VisitState::S7->label());
        $this->assertEquals('会計済', VisitState::S8->label());
        $this->assertEquals('完了', VisitState::S9->label());
    }
}
