<?php

namespace App\Services\Player;

use App\Repositories\PlayerRepository;

class IndexPlayerService
{
    public function __construct(
        private PlayerRepository $playerRepository
    ) {}

    public function execute(): array
    {
        return [$this->playerRepository->all(), 'Successfully recovering players.', 200];
    }
}
