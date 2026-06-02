<?php

namespace Database\Factories;

use App\Models\Championship;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChampionshipFactory extends Factory
{
    protected $model = Championship::class;

    public function definition(): array
    {
        return [
            'team_id'             => Team::factory(),
            'points'              => 0,
            'number_of_goals'     => 0,
            'number_of_victories' => 0,
            'number_of_defeats'   => 0,
        ];
    }
}
