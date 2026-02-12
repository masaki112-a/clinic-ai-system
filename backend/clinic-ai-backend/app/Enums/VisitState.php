<?php

namespace App\Enums;

enum VisitState: string
{
    case S0 = 'S0'; // 未受付
    case S1 = 'S1'; // 受付済
    case S2 = 'S2'; // 待機中
    case S3 = 'S3'; // 呼出中
    case S4 = 'S4'; // 診察中
    case S6 = 'S6'; // 診察終了
    case S7 = 'S7'; // 会計待
    case S8 = 'S8'; // 会計中
    case S9 = 'S9'; // 完了
}
