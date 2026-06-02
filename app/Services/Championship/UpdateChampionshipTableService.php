<?php

namespace App\Services\Championship;

use App\Repositories\ChampionshipMatchsRepository;
use App\Repositories\ChampionshipRepository;
use Illuminate\Support\Facades\DB;

class UpdateChampionshipTableService
{
    public function __construct(
        private ChampionshipMatchsRepository $championshipMatchsRepository,
        private ChampionshipRepository $championshipRepository
    ) {}

    public function execute(int $matchId): void
    {
        $match = $this->championshipMatchsRepository->find($matchId);

        $winner = $this->determineWinner(
            $match->away_team_goals,
            $match->home_team_goals,
            $match->away_team_id,
            $match->home_team_id
        );

        $isDraw = $winner === null;

        $awayChampionship = $this->championshipRepository->allQuery(['team_id' => $match->away_team_id])->first();
        $homeChampionship = $this->championshipRepository->allQuery(['team_id' => $match->home_team_id])->first();

        DB::transaction(function () use ($match, $winner, $isDraw, $awayChampionship, $homeChampionship) {
            $this->championshipRepository->update([
                'points'              => $awayChampionship->points + $this->calculatePoints($winner, $match->away_team_id),
                'number_of_goals'     => $awayChampionship->number_of_goals + $match->away_team_goals,
                'number_of_victories' => $awayChampionship->number_of_victories + $this->calculateVictories($winner, $match->away_team_id),
                'number_of_defeats'   => $awayChampionship->number_of_defeats + $this->calculateDefeats($winner, $match->home_team_id),
                'number_of_draws'     => $awayChampionship->number_of_draws + ($isDraw ? 1 : 0),
            ], $awayChampionship->id);

            $this->championshipRepository->update([
                'points'              => $homeChampionship->points + $this->calculatePoints($winner, $match->home_team_id),
                'number_of_goals'     => $homeChampionship->number_of_goals + $match->home_team_goals,
                'number_of_victories' => $homeChampionship->number_of_victories + $this->calculateVictories($winner, $match->home_team_id),
                'number_of_defeats'   => $homeChampionship->number_of_defeats + $this->calculateDefeats($winner, $match->away_team_id),
                'number_of_draws'     => $homeChampionship->number_of_draws + ($isDraw ? 1 : 0),
            ], $homeChampionship->id);
        });
    }

    private function determineWinner(int $awayGoals, int $homeGoals, int $awayTeamId, int $homeTeamId): ?int
    {
        if ($awayGoals > $homeGoals) {
            return $awayTeamId;
        }

        if ($homeGoals > $awayGoals) {
            return $homeTeamId;
        }

        return null;
    }

    private function calculatePoints(?int $winner, int $teamId): int
    {
        if ($winner === $teamId) {
            return 3;
        }

        if ($winner === null) {
            return 1;
        }

        return 0;
    }

    private function calculateVictories(?int $winner, int $teamId): int
    {
        return $winner === $teamId ? 1 : 0;
    }

    private function calculateDefeats(?int $winner, int $opponentId): int
    {
        return $winner === $opponentId ? 1 : 0;
    }
}
