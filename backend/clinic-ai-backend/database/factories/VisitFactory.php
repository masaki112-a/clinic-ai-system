<?php

namespace Database\Factories;

use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Visit>
 */
class VisitFactory extends Factory
{
    protected $model = Visit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_code' => strtoupper(fake()->bothify('QR######')),
            'current_state' => VisitState::S0->value,
            'is_no_exam' => false,
            'recall_count' => 0,
            'accepted_at' => null,
            'called_at' => null,
            'exam_started_at' => null,
            'exam_ended_at' => null,
            'paid_at' => null,
            'ended_at' => null,
        ];
    }

    /**
     * 受付済み状態
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => VisitState::S1->value,
            'accepted_at' => now(),
        ]);
    }

    /**
     * 待機中状態
     */
    public function waiting(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => VisitState::S2->value,
            'accepted_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * 呼出中状態
     */
    public function calling(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => VisitState::S3->value,
            'accepted_at' => now()->subMinutes(10),
            'called_at' => now(),
        ]);
    }

    /**
     * 診察中状態
     */
    public function inExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => VisitState::S4->value,
            'accepted_at' => now()->subMinutes(20),
            'called_at' => now()->subMinutes(10),
            'exam_started_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * 完了状態
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => VisitState::S9->value,
            'accepted_at' => now()->subMinutes(40),
            'called_at' => now()->subMinutes(30),
            'exam_started_at' => now()->subMinutes(25),
            'exam_ended_at' => now()->subMinutes(15),
            'paid_at' => now()->subMinutes(5),
            'ended_at' => now(),
        ]);
    }

    /**
     * 診察なし会計
     */
    public function noExam(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_state' => VisitState::S7->value,
            'is_no_exam' => true,
            'accepted_at' => now()->subMinutes(10),
        ]);
    }
}

