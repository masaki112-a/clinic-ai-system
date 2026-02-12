<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

enum ExamSessionState: string
{
    case IDLE     = 'idle';
    case CALLING  = 'calling';
    case IN_EXAM  = 'in_exam';
    case FINISHED = 'finished';
}

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'current_state',
        'started_at',
        'ended_at',
        'ai_config_version',
    ];

    public function isState(ExamSessionState $state): bool
    {
        return $this->current_state === $state->value;
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function transition(ExamSessionState $toState): void
    {
        // 簡易版：allowed遷移をチェック
        $allowed = [
            ExamSessionState::IDLE->value     => [ExamSessionState::CALLING->value],
            ExamSessionState::CALLING->value  => [ExamSessionState::IN_EXAM->value],
            ExamSessionState::IN_EXAM->value  => [ExamSessionState::FINISHED->value],
        ];

        $current = $this->current_state;

        if (!in_array($toState->value, $allowed[$current] ?? [])) {
            abort(409, "Invalid session state transition: {$current} → {$toState->value}");
        }

        $this->update(['current_state' => $toState->value]);
    }
}

