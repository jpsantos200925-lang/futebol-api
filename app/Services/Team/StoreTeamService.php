<?php

namespace App\Services\Team;

use App\Repositories\TeamRepository;

class StoreTeamService
{
    public function __construct(
        private TeamRepository $teamRepository
    ) {}

    public function execute(array $data): array
    {
        $team = $this->teamRepository->create($data);
        return [$team, 'Team created successfully.', 201];
    }
}
