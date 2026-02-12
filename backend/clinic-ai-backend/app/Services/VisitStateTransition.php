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
                VisitState::S7->value, // 診察なし例外
            ],
            VisitState::S3->value => [
                VisitState::S4->value,
                VisitState::S5->value, // 再呼出
            ],
            VisitState::S4->value => [VisitState::S6->value],
            VisitState::S5->value => [VisitState::S3->value], // 再呼出から戻る
            VisitState::S6->value => [VisitState::S7->value],
            VisitState::S7->value => [VisitState::S8->value],
            VisitState::S8->value => [VisitState::S9->value],
            VisitState::S9->value => [], // 終端状態
        ];
    }

    public static function can(string $from, string $to): bool
    {
        return in_array($to, self::allowed()[$from] ?? []);
    }

    public static function requiresReason(string $from, string $to): bool
    {
        // S2 → S7 (診察なし会計) は理由必須
        return $from === 'S2' && $to === 'S7';
    }
}

