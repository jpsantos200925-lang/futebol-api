<?php

namespace App\Services\User;

use App\Repositories\UserRepository;

class IndexUserService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function execute(): array
    {
        return [$this->userRepository->all(), 'Successfully recovering users.', 200];
    }
}
