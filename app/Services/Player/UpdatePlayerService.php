<?php

namespace App\Services\Player;

use App\Repositories\PlayerRepository;

class UpdatePlayerService
{
    public function __construct(
        private PlayerRepository $playerRepository
    ) {}

    public function execute(array $data, string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $player = $this->playerRepository->find($id);

        if (is_null($player)) {
            return [$player, 'Player not found.', 404];
        }

        $player = $this->playerRepository->update($data, $id);
        return [$player->refresh(), 'Player updated successfully.', 200];
    }
}
