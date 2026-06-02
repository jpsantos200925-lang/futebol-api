<?php

namespace App\Services\Team;

use App\Repositories\TeamRepository;

class IndexTeamService
{
    public function __construct(
        private TeamRepository $teamRepository
    ) {}

    public function execute(): array
    {
        return [$this->teamRepository->all(), 'Successfully recovering teams.', 200];
    }
}
