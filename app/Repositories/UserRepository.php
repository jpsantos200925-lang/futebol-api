<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository extends BaseRepository
{
    public function getFieldsSearchable(): array
    {
        return [
            'name',
            'email',
        ];
    }

    public function model(): string
    {
        return User::class;
    }
}
