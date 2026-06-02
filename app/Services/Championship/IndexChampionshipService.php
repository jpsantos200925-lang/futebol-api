<?php

namespace App\Services\Championship;

use App\Repositories\ChampionshipRepository;

class IndexChampionshipService
{
    public function __construct(
        private ChampionshipRepository $championshipRepository
    ) {}

    public function execute(): array
    {
        return [$this->championshipRepository->all(), 'Successfully recovering championship.', 200];
    }
}
