<?php

namespace App\Repositories;

use App\Models\Championship;

class ChampionshipRepository extends BaseRepository
{
    public function getFieldsSearchable(): array
    {
        return [
            'team_id',
            'points',
            'number_of_goals',
            'number_of_victories',
            'number_of_defeats',
            'number_of_draws',
        ];
    }

    public function model(): string
    {
        return Championship::class;
    }
}
