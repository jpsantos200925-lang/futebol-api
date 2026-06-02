<?php

namespace App\Repositories;

use App\Models\ChampionshipMatchs;

class ChampionshipMatchsRepository extends BaseRepository
{
    public function getFieldsSearchable(): array
    {
        return [
            'date',
            'start_time',
            'end_time',
            'away_team_id',
            'home_team_id',
            'away_team_goals',
            'home_team_goals',
            'is_ended',
        ];
    }

    public function model(): string
    {
        return ChampionshipMatchs::class;
    }

    public function deleteByTeamId(int $teamId): void
    {
        $this->model->newQuery()
            ->where('away_team_id', $teamId)
            ->orWhere('home_team_id', $teamId)
            ->delete();
    }
}
