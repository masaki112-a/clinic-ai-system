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

    /**
     * 許可された遷移パターン（憲法第10条準拠）
     */
    public function allowedTransitions(): array
    {
        return match($this) {
            self::S0 => [self::S1],
            self::S1 => [self::S2],
            self::S2 => [self::S3, self::S7], // 診察なし例外
            self::S3 => [self::S4, self::S5], // 診察開始 or 再呼出
            self::S4 => [self::S6],
            self::S5 => [self::S3], // 再呼出→呼出中に戻る
            self::S6 => [self::S7],
            self::S7 => [self::S8],
            self::S8 => [self::S9],
            self::S9 => [], // 終端状態
        };
    }

    /**
     * 遷移可能かチェック
     */
    public function canTransitionTo(VisitState $to): bool
    {
        return in_array($to, $this->allowedTransitions());
    }

    /**
     * 表示名取得
     */
    public function label(): string
    {
        return match($this) {
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
}
