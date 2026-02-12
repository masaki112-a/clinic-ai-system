<?php

namespace App\Enums;

enum VisitState: string
{
    case S0 = 'S0'; // 未受付
    case S1 = 'S1'; // 受付済
    case S2 = 'S2'; // 待機中
    case S3 = 'S3'; // 呼出中
    case S4 = 'S4'; // 診察中
    case S5 = 'S5'; // 再呼出
    case S6 = 'S6'; // 診察終了
    case S7 = 'S7'; // 会計待
    case S8 = 'S8'; // 会計中
    case S9 = 'S9'; // 完了

    public function label(): string
    {
        return match ($this) {
            self::S0 => '未受付',
            self::S1 => '受付済',
            self::S2 => '待機中',
            self::S3 => '呼出中',
            self::S4 => '診察中',
            self::S5 => '再呼出',
            self::S6 => '診察終了',
            self::S7 => '会計待',
            self::S8 => '会計中',
            self::S9 => '完了',
        };
    }

    public function canTransitionTo(VisitState $to): bool
    {
        $allowed = [
            self::S0->value => [self::S1->value],
            self::S1->value => [self::S2->value],
            self::S2->value => [self::S3->value, self::S7->value], // S7 = 診察なし会計
            self::S3->value => [self::S4->value, self::S5->value], // S5 = 再呼出
            self::S4->value => [self::S6->value],
            self::S5->value => [self::S3->value], // 再呼出から呼出中に戻る
            self::S6->value => [self::S7->value],
            self::S7->value => [self::S8->value],
            self::S8->value => [self::S9->value],
            self::S9->value => [], // 終端状態
        ];

        return in_array($to->value, $allowed[$this->value] ?? []);
    }
}

