<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StateLog extends Model
{
    protected $fillable = [
        'visit_id',
        'from_state',
        'to_state',
        'reason',
        'changed_at',
    ];

}
