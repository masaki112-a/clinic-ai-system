<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UiLockLog extends Model
{
    protected $fillable = [
        'ui_name',
        'action',
        'operator',
        'reason',
        'occurred_at',
    ];
}
