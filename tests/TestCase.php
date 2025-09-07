<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear JWT tokens between tests
        JWTAuth::unsetToken();
    }

    /**
     * Authenticate a user and return the token
     */
    protected function authenticateUser($user = null): string
    {
        $user = $user ?: \App\Models\User::factory()->regularUser()->create();

        return JWTAuth::fromUser($user);
    }

    /**
     * Get authorization header with JWT token
     */
    protected function getAuthHeader($token = null): array
    {
        $token = $token ?: $this->authenticateUser();

        return ['Authorization' => "Bearer {$token}"];
    }
}
