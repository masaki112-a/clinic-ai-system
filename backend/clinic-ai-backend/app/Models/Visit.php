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
        'is_no_exam',        // 追加
        'recall_count',      // 追加
        'accepted_at',
        'waiting_started_at',
        'called_at',
        'exam_started_at',
        'exam_ended_at',
        'payment_amount',
        'insurance_type',
        'payment_ready_at',
        'payment_called_at',
        'paid_at',
        'ended_at',          // 追加
    ];

    protected $casts = [
        'current_state' => VisitState::class,
        'is_no_exam' => 'boolean',
        'recall_count' => 'integer',
        'accepted_at' => 'datetime',
        'waiting_started_at' => 'datetime',
        'called_at' => 'datetime',
        'exam_started_at' => 'datetime',
        'exam_ended_at' => 'datetime',
        'payment_ready_at' => 'datetime',
        'payment_called_at' => 'datetime',
        'paid_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function examSession()
    {
        return $this->hasOne(ExamSession::class);
    }

    public function stateLogs()
    {
        return $this->hasMany(StateLog::class);
    }

    /**
     * 再呼出可能かチェック
     */
    public function canRecall(): bool
    {
        return $this->current_state === VisitState::S3
            && $this->recall_count < 3; // 最大3回まで
    }

    /**
     * 診察なし会計かチェック
     */
    public function isNoExam(): bool
    {
        return $this->is_no_exam === true;
    }
}

