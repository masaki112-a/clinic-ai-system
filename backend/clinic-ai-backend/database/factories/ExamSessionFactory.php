<?php

namespace Database\Factories;

use App\Models\ExamSession;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExamSession>
 */
class ExamSessionFactory extends Factory
{
    protected $model = ExamSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'current_state' => 'idle',
            'started_at' => null,
            'ended_at' => null,
            'ai_config_version' => null,
        ];
    }

    /**
     * 診察中状態
     */
    public function inExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => 'in_exam',
            'started_at' => now()->subMinutes(10),
            'ai_config_version' => 'snapshot_' . now()->format('YmdHis'),
        ]);
    }

    /**
     * 完了状態
     */
    public function finished(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => 'finished',
            'started_at' => now()->subMinutes(20),
            'ended_at' => now(),
            'ai_config_version' => 'snapshot_' . now()->subMinutes(20)->format('YmdHis'),
        ]);
    }
}
