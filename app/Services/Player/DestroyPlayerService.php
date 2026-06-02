<?php

namespace App\Services\Player;

use App\Repositories\PlayerRepository;

class DestroyPlayerService
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

        $this->playerRepository->delete($id);
        return [$player, 'Player deleted successfully.', 200];
    }
}
