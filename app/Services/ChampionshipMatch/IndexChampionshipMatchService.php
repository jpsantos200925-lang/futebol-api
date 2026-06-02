<?php

namespace App\Services\ChampionshipMatch;

use App\Repositories\ChampionshipMatchsRepository;

class IndexChampionshipMatchService
{
    public function __construct(
        private ChampionshipMatchsRepository $championshipMatchsRepository
    ) {}

    public function execute(): array
    {
        return [$this->championshipMatchsRepository->all(), 'Successfully recovering championship matches.', 200];
    }
}
