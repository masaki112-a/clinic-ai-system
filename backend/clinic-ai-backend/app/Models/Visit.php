<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Enums\VisitState;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_code',
        'current_state', 
        'accepted_at',
        'called_at',
        'exam_started_at',
        'exam_ended_at',
        'paid_at',
    ];

    protected $casts = [
        'current_state' => VisitState::class,
    ];

    public function examSession()
    {
        return $this->hasOne(ExamSession::class);
    }
}

