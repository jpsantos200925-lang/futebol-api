<?php

namespace App\Services\User;

use App\Repositories\UserRepository;

class StoreUserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function execute(array $data): array
    {
        $user = $this->userRepository->create($data);
        return [$user, 'User created successfully.', 201];
    }
}
