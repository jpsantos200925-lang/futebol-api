<?php

namespace Database\Factories;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlayerFactory extends Factory
{
    protected $model = Player::class;

    public function definition(): array
    {
        return [
            'name'    => fake()->name(),
            'number'  => fake()->unique()->numberBetween(1, 99),
            'team_id' => Team::factory(),
        ];
    }
}
