<?php

namespace App\Services\ChampionshipMatch;

use App\Events\EndOfTheMatch;
use App\Exceptions\Domain\MatchAlreadyEndedException;
use App\Repositories\ChampionshipMatchsRepository;

class UpdateChampionshipMatchService
{
    public function __construct(
        private ChampionshipMatchsRepository $championshipMatchsRepository
    ) {}

    public function execute(array $data, string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID.', 404];
        }

        $match = $this->championshipMatchsRepository->find($id);

        if (is_null($match)) {
            return [$match, 'Championship Match not found.', 404];
        }

        if ($match->is_ended) {
            throw new MatchAlreadyEndedException((int) $id);
        }

        $isEnding = $data['is_ended'] ?? false;

        if ($isEnding) {
            $data['end_time'] = now()->toTimeString();
        }

        $this->championshipMatchsRepository->update($data, $id);

        if ($isEnding) {
            EndOfTheMatch::dispatch($id);
        }

        return [$match->refresh(), 'Match in Championship updated successfully.', 200];
    }
}
