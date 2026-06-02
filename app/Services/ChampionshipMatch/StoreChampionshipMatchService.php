<?php

namespace App\Services\ChampionshipMatch;

use App\Repositories\ChampionshipMatchsRepository;

class StoreChampionshipMatchService
{
    public function __construct(
        private ChampionshipMatchsRepository $championshipMatchsRepository
    ) {}

    public function execute(array $data): array
    {
        $match = $this->championshipMatchsRepository->create($data);
        return [$match, 'Match in Championship successfully.', 201];
    }
}
