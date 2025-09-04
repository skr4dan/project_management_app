<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use PHPUnit\Framework\Attributes\Test;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private $userRepositoryMock;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepositoryMock);

        $this->user = new User([
            'id' => 1,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->user->exists = true;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_authenticate_user_with_valid_credentials()
    {
        $credentials = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->with($credentials)
            ->andReturn('jwt-token-123');

        JWTAuth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $result = $this->authService->login($credentials);

        $this->assertEquals([
            'access_token' => 'jwt-token-123',
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $this->user,
        ], $result);
    }

    #[Test]
    public function it_throws_exception_when_login_credentials_are_invalid()
    {
        $credentials = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ];

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->with($credentials)
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login($credentials);
    }

    #[Test]
    public function it_can_register_new_user()
    {
        $this->userRepositoryMock
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn(null);

        $this->userRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($attributes) {
                return $attributes['name'] === 'John Doe'
                    && $attributes['email'] === 'john@example.com'
                    && Hash::check('password123', $attributes['password']);
            }))
            ->andReturn($this->user);

        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with($this->user)
            ->andReturn('jwt-token-123');

        $result = $this->authService->register([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $this->assertEquals([
            'access_token' => 'jwt-token-123',
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user' => $this->user,
        ], $result);
    }

    #[Test]
    public function it_throws_exception_when_registering_with_existing_email()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $this->userRepositoryMock
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User already exists with this email');

        $this->authService->register($userData);
    }

    #[Test]
    public function it_can_logout_user()
    {
        JWTAuth::shouldReceive('getToken')
            ->once()
            ->withNoArgs()
            ->andReturn('jwt-token-123');

        JWTAuth::shouldReceive('invalidate')
            ->once()
            ->with('jwt-token-123')
            ->andReturn(true);

        $this->authService->logout();

        // No assertions needed, just verifying the method runs without errors
        $this->assertTrue(true);
    }

    #[Test]
    public function it_can_refresh_jwt_token()
    {
        JWTAuth::shouldReceive('refresh')
            ->once()
            ->andReturn('new-jwt-token-456');

        $result = $this->authService->refresh();

        $this->assertEquals([
            'access_token' => 'new-jwt-token-456',
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ], $result);
    }

    #[Test]
    public function it_can_get_authenticated_user()
    {
        JWTAuth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        $result = $this->authService->user();

        $this->assertEquals($this->user, $result);
    }

    #[Test]
    public function it_can_check_if_user_is_authenticated()
    {
        JWTAuth::shouldReceive('check')
            ->once()
            ->andReturn(true);

        $result = $this->authService->check();

        $this->assertTrue($result);
    }

    #[Test]
    public function it_returns_false_when_user_is_not_authenticated()
    {
        JWTAuth::shouldReceive('check')
            ->once()
            ->andReturn(false);

        $result = $this->authService->check();

        $this->assertFalse($result);
    }

    #[Test]
    public function it_returns_null_when_no_authenticated_user()
    {
        JWTAuth::shouldReceive('user')
            ->once()
            ->andReturn(null);

        $result = $this->authService->user();

        $this->assertNull($result);
    }

    #[Test]
    public function it_hashes_password_when_registering()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'plaintextpassword',
        ];

        $this->userRepositoryMock
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn(null);

        $this->userRepositoryMock
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($data) {
                return $data['name'] === 'John Doe'
                    && $data['email'] === 'john@example.com'
                    && Hash::check('plaintextpassword', $data['password']);
            }))
            ->andReturn($this->user);

        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with($this->user)
            ->andReturn('jwt-token-123');

        $result = $this->authService->register($userData);

        $this->assertEquals('jwt-token-123', $result['access_token']);
    }
}
