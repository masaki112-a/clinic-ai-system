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
            VisitState::S3->value => [VisitState::S4->value],
            VisitState::S4->value => [VisitState::S6->value],
            VisitState::S6->value => [VisitState::S7->value],
            VisitState::S7->value => [VisitState::S8->value],
            VisitState::S8->value => [VisitState::S9->value],
        ];
    }

    public static function can(string $from, string $to): bool
    {
        return in_array($to, self::allowed()[$from] ?? []);
    }
}
