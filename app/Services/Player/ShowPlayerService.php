<?php

namespace App\Services\Player;

use App\Repositories\PlayerRepository;

class ShowPlayerService
{
    public function __construct(
        private PlayerRepository $playerRepository
    ) {}

    public function execute(string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $player = $this->playerRepository->find($id);

        if (is_null($player)) {
            return [$player, 'Player not found.', 404];
        }

        return [$player, 'Player found successfully.', 200];
    }
}
