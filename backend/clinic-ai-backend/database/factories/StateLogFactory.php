<?php

namespace Database\Factories;

use App\Models\StateLog;
use App\Models\Visit;
use App\Enums\VisitState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StateLog>
 */
class StateLogFactory extends Factory
{
    protected $model = StateLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'visit_id' => Visit::factory(),
            'from_state' => VisitState::S0->value,
            'to_state' => VisitState::S1->value,
            'reason' => null,
            'changed_at' => now(),
        ];
    }
}
