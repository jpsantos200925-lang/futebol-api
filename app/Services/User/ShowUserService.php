<?php

namespace App\Services\User;

use App\Repositories\UserRepository;

class ShowUserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function execute(string $id): array
    {
        if (!is_numeric($id)) {
            return [[], 'Invalid ID', 404];
        }

        $user = $this->userRepository->find($id);

        if (is_null($user)) {
            return [$user, 'User not found', 404];
        }

        return [$user, 'User found successfully', 200];
    }
}
