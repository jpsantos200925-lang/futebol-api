<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Services\User\DestroyUserService;
use App\Services\User\IndexUserService;
use App\Services\User\ShowUserService;
use App\Services\User\StoreUserService;
use App\Services\User\UpdateUserService;

class UserController extends Controller
{
    public function __construct(
        private IndexUserService $indexUserService,
        private StoreUserService $storeUserService,
        private ShowUserService $showUserService,
        private UpdateUserService $updateUserService,
        private DestroyUserService $destroyUserService
    ) {}

    public function index()
    {
        [$data, $message, $status] = $this->indexUserService->execute();
        return $this->return_default($data, $message, $status);
    }

    public function store(StoreUserRequest $request)
    {
        [$data, $message, $status] = $this->storeUserService->execute($request->validated());
        return $this->return_default($data, $message, $status);
    }

    public function show(string $id)
    {
        [$data, $message, $status] = $this->showUserService->execute($id);
        return $this->return_default($data, $message, $status);
    }

    public function update(UpdateUserRequest $request, string $id)
    {
        [$data, $message, $status] = $this->updateUserService->execute($request->validated(), $id);
        return $this->return_default($data, $message, $status);
    }

    public function destroy(string $id)
    {
        [$data, $message, $status] = $this->destroyUserService->execute($id);
        return $this->return_default($data, $message, $status);
    }
}
