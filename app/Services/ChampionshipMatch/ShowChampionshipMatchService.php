<?php

namespace App\Services\ChampionshipMatch;

use App\Repositories\ChampionshipMatchsRepository;

class ShowChampionshipMatchService
{
    public function __construct(
        private ChampionshipMatchsRepository $championshipMatchsRepository
    ) {}

    public function execute(string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $match = $this->championshipMatchsRepository->find($id);

        if (is_null($match)) {
            return [$match, 'Championship Match not found.', 404];
        }

        return [$match, 'Match in Championship found successfully.', 200];
    }
}
