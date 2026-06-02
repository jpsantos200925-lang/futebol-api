<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function createUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'cognito_sub' => 'test-sub-' . uniqid(),
        ], $overrides));
    }

    protected function actingAsUser(?User $user = null): static
    {
        $user ??= $this->createUser();
        return $this->actingAs($user, 'cognito');
    }
}
