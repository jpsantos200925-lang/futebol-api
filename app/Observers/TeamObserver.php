<?php

namespace App\Observers;

use App\Models\Team;
use App\Repositories\ChampionshipMatchsRepository;
use App\Repositories\ChampionshipRepository;

class TeamObserver
{
    public function __construct(
        private ChampionshipRepository $championshipRepository,
        private ChampionshipMatchsRepository $championshipMatchsRepository
    ) {}

    public function created(Team $team): void
    {
        $this->championshipRepository->create(['team_id' => $team->id]);
    }

    public function deleted(Team $team): void
    {
        $this->championshipMatchsRepository->deleteByTeamId($team->id);

        $championship = $this->championshipRepository->allQuery(['team_id' => $team->id])->first();
        if ($championship) {
            $this->championshipRepository->delete($championship->id);
        }
    }

    public function restored(Team $team): void
    {
        $this->created($team);
    }

    public function forceDeleted(Team $team): void
    {
        $this->deleted($team);
    }
}
