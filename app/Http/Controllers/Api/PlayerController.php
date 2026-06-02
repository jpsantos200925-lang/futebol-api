<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Player\StorePlayerRequest;
use App\Http\Requests\Player\UpdatePlayerRequest;
use App\Services\Player\DestroyPlayerService;
use App\Services\Player\IndexPlayerService;
use App\Services\Player\ShowPlayerService;
use App\Services\Player\StorePlayerService;
use App\Services\Player\UpdatePlayerService;

class PlayerController extends Controller
{
    public function __construct(
        private IndexPlayerService $indexPlayerService,
        private StorePlayerService $storePlayerService,
        private ShowPlayerService $showPlayerService,
        private UpdatePlayerService $updatePlayerService,
        private DestroyPlayerService $destroyPlayerService
    ) {
        $this->middleware('auth:cognito');
    }

    public function index()
    {
        [$data, $message, $status] = $this->indexPlayerService->execute();
        return $this->return_default($data, $message, $status);
    }

    public function store(StorePlayerRequest $request)
    {
        [$data, $message, $status] = $this->storePlayerService->execute($request->validated());
        return $this->return_default($data, $message, $status);
    }

    public function show(string $id)
    {
        [$data, $message, $status] = $this->showPlayerService->execute($id);
        return $this->return_default($data, $message, $status);
    }

    public function update(UpdatePlayerRequest $request, string $id)
    {
        [$data, $message, $status] = $this->updatePlayerService->execute($request->validated(), $id);
        return $this->return_default($data, $message, $status);
    }

    public function destroy(string $id)
    {
        [$data, $message, $status] = $this->destroyPlayerService->execute($id);
        return $this->return_default($data, $message, $status);
    }
}
