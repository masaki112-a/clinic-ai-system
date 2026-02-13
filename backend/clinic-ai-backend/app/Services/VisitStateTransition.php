<?php

namespace App\Services;

use App\Enums\VisitState;

class VisitStateTransition
{
    public static function allowed(): array
    {
        return [
            VisitState::S0->value => [VisitState::S1->value],
            VisitState::S1->value => [VisitState::S2->value],
            VisitState::S2->value => [
                VisitState::S3->value,
                VisitState::S6->value, // 診察なし例外
            ],
            VisitState::S3->value => [
                VisitState::S4->value,  // 診察開始
                VisitState::S5->value,  // 再呼出
            ],
            VisitState::S4->value => [VisitState::S6->value],
            VisitState::S5->value => [VisitState::S3->value], // 再呼出→呼出中
            VisitState::S6->value => [VisitState::S6_5->value],
            VisitState::S6_5->value => [VisitState::S7->value],
            VisitState::S7->value => [VisitState::S8->value],
            VisitState::S8->value => [VisitState::S9->value],
            VisitState::S9->value => [], // 終端
        ];
    }

    public static function can(string $from, string $to): bool
    {
        return in_array($to, self::allowed()[$from] ?? []);
    }

    /**
     * 遷移理由の検証（憲法第10条：例外処理）
     */
    public static function requiresReason(string $from, string $to): bool
    {
        // S2→S6（診察なし会計）は理由必須
        return $from === VisitState::S2->value 
            && $to === VisitState::S6->value;
    }
}
