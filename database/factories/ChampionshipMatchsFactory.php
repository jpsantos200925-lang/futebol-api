<?php

namespace Database\Factories;

use App\Models\ChampionshipMatchs;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChampionshipMatchsFactory extends Factory
{
    protected $model = ChampionshipMatchs::class;

    public function definition(): array
    {
        return [
            'date'            => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'start_time'      => '15:00:00',
            'end_time'        => null,
            'away_team_id'    => Team::factory(),
            'home_team_id'    => Team::factory(),
            'away_team_goals' => 0,
            'home_team_goals' => 0,
            'is_ended'        => false,
        ];
    }

    public function ended(int $awayGoals = 1, int $homeGoals = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'away_team_goals' => $awayGoals,
            'home_team_goals' => $homeGoals,
            'is_ended'        => true,
            'end_time'        => '17:00:00',
        ]);
    }
}
