<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UiLock extends Model
{
    protected $fillable = [
        'ui_name',
        'locked_by',
        'locked_at',
        'expires_at',
    ];
}
