<?php

namespace App\Listeners;

use App\Events\EndOfTheMatch;
use App\Services\Championship\UpdateChampionshipTableService;

class UpdateChampionshipTable
{
    public function __construct(
        private UpdateChampionshipTableService $updateChampionshipTableService
    ) {}

    public function handle(EndOfTheMatch $event): void
    {
        $this->updateChampionshipTableService->execute($event->championship_match_id);
    }
}
