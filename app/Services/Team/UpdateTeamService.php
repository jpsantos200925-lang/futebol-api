<?php

namespace App\Services\Team;

use App\Repositories\TeamRepository;

class UpdateTeamService
{
    public function __construct(
        private TeamRepository $teamRepository
    ) {}

    public function execute(array $data, string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $team = $this->teamRepository->find($id);

        if (is_null($team)) {
            return [$team, 'Team not found.', 404];
        }

        $team = $this->teamRepository->update($data, $id);
        return [$team->refresh(), 'Team updated successfully.', 200];
    }
}
