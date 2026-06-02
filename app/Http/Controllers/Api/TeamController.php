<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Services\Team\DestroyTeamService;
use App\Services\Team\IndexTeamService;
use App\Services\Team\ShowTeamService;
use App\Services\Team\StoreTeamService;
use App\Services\Team\UpdateTeamService;

class TeamController extends Controller
{
    public function __construct(
        private IndexTeamService $indexTeamService,
        private StoreTeamService $storeTeamService,
        private ShowTeamService $showTeamService,
        private UpdateTeamService $updateTeamService,
        private DestroyTeamService $destroyTeamService
    ) {
        $this->middleware('auth:cognito');
    }

    public function index()
    {
        [$data, $message, $status] = $this->indexTeamService->execute();
        return $this->return_default($data, $message, $status);
    }

    public function store(StoreTeamRequest $request)
    {
        [$data, $message, $status] = $this->storeTeamService->execute($request->validated());
        return $this->return_default($data, $message, $status);
    }

    public function show(string $id)
    {
        [$data, $message, $status] = $this->showTeamService->execute($id);
        return $this->return_default($data, $message, $status);
    }

    public function update(UpdateTeamRequest $request, string $id)
    {
        [$data, $message, $status] = $this->updateTeamService->execute($request->validated(), $id);
        return $this->return_default($data, $message, $status);
    }

    public function destroy(string $id)
    {
        [$data, $message, $status] = $this->destroyTeamService->execute($id);
        return $this->return_default($data, $message, $status);
    }
}
