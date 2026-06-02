<?php

namespace App\Services\Team;

use App\Repositories\TeamRepository;

class ShowTeamService
{
    public function __construct(
        private TeamRepository $teamRepository
    ) {}

    public function execute(string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $team = $this->teamRepository->find($id);

        if (is_null($team)) {
            return [[], 'Team not found.', 404];
        }

        $team['players'] = $team->players;

        return [$team, 'Team found successfully.', 200];
    }
}
