<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Championship\StoreChampionshipRequest;
use App\Http\Requests\Championship\UpdateChampionshipRequest;
use App\Services\Championship\DestroyChampionshipService;
use App\Services\Championship\IndexChampionshipService;
use App\Services\Championship\ShowChampionshipService;
use App\Services\Championship\StoreChampionshipService;
use App\Services\Championship\UpdateChampionshipService;

class ChampionshipController extends Controller
{
    public function __construct(
        private IndexChampionshipService $indexChampionshipService,
        private StoreChampionshipService $storeChampionshipService,
        private ShowChampionshipService $showChampionshipService,
        private UpdateChampionshipService $updateChampionshipService,
        private DestroyChampionshipService $destroyChampionshipService
    ) {
        $this->middleware('auth:cognito');
    }

    public function index()
    {
        [$data, $message, $status] = $this->indexChampionshipService->execute();
        return $this->return_default($data, $message, $status);
    }

    public function store(StoreChampionshipRequest $request)
    {
        [$data, $message, $status] = $this->storeChampionshipService->execute($request->validated());
        return $this->return_default($data, $message, $status);
    }

    public function show(string $id)
    {
        [$data, $message, $status] = $this->showChampionshipService->execute($id);
        return $this->return_default($data, $message, $status);
    }

    public function update(UpdateChampionshipRequest $request, string $id)
    {
        [$data, $message, $status] = $this->updateChampionshipService->execute($request->validated(), $id);
        return $this->return_default($data, $message, $status);
    }

    public function destroy(string $id)
    {
        [$data, $message, $status] = $this->destroyChampionshipService->execute($id);
        return $this->return_default($data, $message, $status);
    }
}
