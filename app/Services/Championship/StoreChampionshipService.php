<?php

namespace App\Services\Championship;

use App\Repositories\ChampionshipRepository;

class StoreChampionshipService
{
    public function __construct(
        private ChampionshipRepository $championshipRepository
    ) {}

    public function execute(array $data): array
    {
        $championship = $this->championshipRepository->create($data);
        return [$championship, 'Championship created successfully.', 201];
    }
}
