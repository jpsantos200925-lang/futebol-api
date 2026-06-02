<?php

namespace App\Services\Championship;

use App\Repositories\ChampionshipRepository;

class ShowChampionshipService
{
    public function __construct(
        private ChampionshipRepository $championshipRepository
    ) {}

    public function execute(string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $championship = $this->championshipRepository->find($id);

        if (is_null($championship)) {
            return [$championship, 'Championship not found.', 404];
        }

        return [$championship, 'Championship found successfully.', 200];
    }
}
