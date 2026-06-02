<?php

namespace App\Repositories;

use App\Models\Team;

class TeamRepository extends BaseRepository
{
    public function getFieldsSearchable(): array
    {
        return [
            'name',
        ];
    }

    public function model(): string
    {
        return Team::class;
    }
}
