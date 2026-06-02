<?php

namespace App\Repositories;

use App\Models\Player;

class PlayerRepository extends BaseRepository
{
    public function getFieldsSearchable(): array
    {
        return [
            'name',
            'number',
            'team_id',
        ];
    }

    public function model(): string
    {
        return Player::class;
    }
}
