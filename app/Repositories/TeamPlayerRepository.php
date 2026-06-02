<?php

namespace App\Repositories;

use App\Models\TeamPlayer;

class TeamPlayerRepository extends BaseRepository
{
    public function getFieldsSearchable(): array
    {
        return [
            'player_id',
            'team_id',
        ];
    }

    public function model(): string
    {
        return TeamPlayer::class;
    }
}
