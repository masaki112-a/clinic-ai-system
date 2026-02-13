<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\VisitStateTransition;
use App\Enums\VisitState;

class VisitStateTransitionTest extends TestCase
{
    /** @test */
    public function 許可された遷移パターンが正しい()
    {
        $allowed = VisitStateTransition::allowed();

        // S0 → S1
        $this->assertContains(VisitState::S1->value, $allowed[VisitState::S0->value]);

        // S3 → S4, S5
        $this->assertContains(VisitState::S4->value, $allowed[VisitState::S3->value]);
        $this->assertContains(VisitState::S5->value, $allowed[VisitState::S3->value]);

        // S5 → S3
        $this->assertContains(VisitState::S3->value, $allowed[VisitState::S5->value]);

        // S9は終端状態
        $this->assertEmpty($allowed[VisitState::S9->value]);
    }

    /** @test */
    public function can_正常系()
    {
        // S0 → S1
        $this->assertTrue(VisitStateTransition::can('S0', 'S1'));

        // S2 → S3
        $this->assertTrue(VisitStateTransition::can('S2', 'S3'));

        // S3 → S5
        $this->assertTrue(VisitStateTransition::can('S3', 'S5'));
    }

    /** @test */
    public function can_異常系()
    {
        // S0 → S9
        $this->assertFalse(VisitStateTransition::can('S0', 'S9'));

        // S2 → S4
        $this->assertFalse(VisitStateTransition::can('S2', 'S4'));

        // S9 → S0
        $this->assertFalse(VisitStateTransition::can('S9', 'S0'));
    }

    /** @test */
    public function 診察なし会計は理由が必須()
    {
        // S2 → S6 は理由必須
        $this->assertTrue(VisitStateTransition::requiresReason('S2', 'S6'));

        // 他の遷移は理由不要
        $this->assertFalse(VisitStateTransition::requiresReason('S0', 'S1'));
        $this->assertFalse(VisitStateTransition::requiresReason('S3', 'S4'));
    }
}
