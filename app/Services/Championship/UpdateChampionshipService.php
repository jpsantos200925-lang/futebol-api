<?php

namespace App\Services\Championship;

use App\Repositories\ChampionshipRepository;

class UpdateChampionshipService
{
    public function __construct(
        private ChampionshipRepository $championshipRepository
    ) {}

    public function execute(array $data, string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $championship = $this->championshipRepository->find($id);

        if (is_null($championship)) {
            return [$championship, 'Championship not found.', 404];
        }

        $championship = $this->championshipRepository->update($data, $id);
        return [$championship->refresh(), 'Championship updated successfully.', 200];
    }
}
