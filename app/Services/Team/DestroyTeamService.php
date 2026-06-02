<?php

namespace App\Services\Team;

use App\Repositories\TeamRepository;

class DestroyTeamService
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
            return [$team, 'Team not found.', 404];
        }

        $this->teamRepository->delete($id);
        return [$team, 'Team deleted successfully.', 200];
    }
}
