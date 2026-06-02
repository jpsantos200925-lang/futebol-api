<?php

namespace App\Services\Player;

use App\Exceptions\Domain\ShirtNumberAlreadyInUseException;
use App\Repositories\PlayerRepository;
use App\Repositories\TeamRepository;
use Illuminate\Support\Facades\DB;

class StorePlayerService
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private TeamRepository $teamRepository
    ) {}

    public function execute(array $players): array
    {
        foreach ($players as $playerData) {
            $this->validateShirtNumber($playerData);
        }

        $created = DB::transaction(function () use ($players) {
            $result = [];
            foreach ($players as $playerData) {
                $result[] = $this->playerRepository->create($playerData);
            }
            return $result;
        });

        return [$created, 'Players created successfully.', 201];
    }

    private function validateShirtNumber(array $data): void
    {
        $existing = $this->playerRepository->allQuery(['number' => $data['number'], 'team_id' => $data['team_id']])->first();
        $teamName = $this->teamRepository->find($data['team_id'])->name;

        if (!is_null($existing)) {
            throw new ShirtNumberAlreadyInUseException($data['number'], $teamName);
        }
    }
}
