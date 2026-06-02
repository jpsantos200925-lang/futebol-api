<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChampionshipMatch\StoreChampionshipMatchRequest;
use App\Http\Requests\ChampionshipMatch\UpdateChampionshipMatchRequest;
use App\Services\ChampionshipMatch\DestroyChampionshipMatchService;
use App\Services\ChampionshipMatch\IndexChampionshipMatchService;
use App\Services\ChampionshipMatch\ShowChampionshipMatchService;
use App\Services\ChampionshipMatch\StoreChampionshipMatchService;
use App\Services\ChampionshipMatch\UpdateChampionshipMatchService;

class ChampionshipMatchController extends Controller
{
    public function __construct(
        private IndexChampionshipMatchService $indexChampionshipMatchService,
        private StoreChampionshipMatchService $storeChampionshipMatchService,
        private ShowChampionshipMatchService $showChampionshipMatchService,
        private UpdateChampionshipMatchService $updateChampionshipMatchService,
        private DestroyChampionshipMatchService $destroyChampionshipMatchService
    ) {
        $this->middleware('auth:cognito');
    }

    public function index()
    {
        [$data, $message, $status] = $this->indexChampionshipMatchService->execute();
        return $this->return_default($data, $message, $status);
    }

    public function store(StoreChampionshipMatchRequest $request)
    {
        [$data, $message, $status] = $this->storeChampionshipMatchService->execute($request->validated());
        return $this->return_default($data, $message, $status);
    }

    public function show(string $id)
    {
        [$data, $message, $status] = $this->showChampionshipMatchService->execute($id);
        return $this->return_default($data, $message, $status);
    }

    public function update(UpdateChampionshipMatchRequest $request, string $id)
    {
        [$data, $message, $status] = $this->updateChampionshipMatchService->execute($request->validated(), $id);
        return $this->return_default($data, $message, $status);
    }

    public function destroy(string $id)
    {
        [$data, $message, $status] = $this->destroyChampionshipMatchService->execute($id);
        return $this->return_default($data, $message, $status);
    }
}
