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
        'is_no_exam',
        'recall_count',
        'accepted_at',
        'called_at',
        'exam_started_at',
        'exam_ended_at',
        'paid_at',
        'ended_at',
    ];

    protected $casts = [
        'current_state' => VisitState::class,
        'is_no_exam' => 'boolean',
    ];

    public function examSession()
    {
        return $this->hasOne(ExamSession::class);
    }

    public function canRecall(): bool
    {
        return $this->current_state === VisitState::S3
            && $this->recall_count < config('clinic.max_recall_count', 3);
    }

    public function isNoExam(): bool
    {
        return $this->is_no_exam;
    }
}

