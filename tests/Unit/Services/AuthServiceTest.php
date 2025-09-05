<?php

namespace Tests\Unit\Services;

use App\DTOs\Auth\AuthResponseDTO;
use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\DTOs\Auth\TokenResponseDTO;
use App\Models\Role;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
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
    private $roleRepositoryMock;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepositoryMock = Mockery::mock(UserRepositoryInterface::class);
        $this->roleRepositoryMock = Mockery::mock(RoleRepositoryInterface::class);
        $this->authService = new AuthService($this->userRepositoryMock, $this->roleRepositoryMock);

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
        $loginDTO = new LoginDTO(
            email: 'john@example.com',
            password: 'password123'
        );

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->with($loginDTO->toArray())
            ->andReturn('jwt-token-123');

        JWTAuth::shouldReceive('user')
            ->once()
            ->andReturn($this->user);

        /** @var AuthResponseDTO $result */
        $result = $this->authService->login($loginDTO);

        $this->assertInstanceOf(AuthResponseDTO::class, $result);
        $this->assertEquals('jwt-token-123', $result->access_token);
        $this->assertEquals('Bearer', $result->token_type);
        $this->assertEquals(config('jwt.ttl') * 60, $result->expires_in);
        $this->assertEquals($this->user, $result->user);
    }

    #[Test]
    public function it_throws_exception_when_login_credentials_are_invalid()
    {
        $loginDTO = new LoginDTO(
            email: 'john@example.com',
            password: 'wrongpassword'
        );

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->with($loginDTO->toArray())
            ->andReturn(false);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login($loginDTO);
    }

    #[Test]
    public function it_can_register_new_user()
    {
        $registerDTO = new RegisterDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123'
        );

        $this->roleRepositoryMock
            ->shouldReceive('findBySlug')
            ->once()
            ->with('user')
            ->andReturn(Role::factory()->makeOne(['slug' => 'user']));

        $this->userRepositoryMock
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn(null);

        $this->userRepositoryMock
            ->shouldReceive('createFromDTO')
            ->once()
            ->with(Mockery::on(function ($userDTO) {
                return $userDTO->first_name === 'John'
                    && $userDTO->last_name === 'Doe'
                    && $userDTO->email === 'john@example.com'
                    && Hash::check('password123', $userDTO->password);
            }))
            ->andReturn($this->user);

        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with($this->user)
            ->andReturn('jwt-token-123');

        /** @var AuthResponseDTO $result */
        $result = $this->authService->register($registerDTO);

        $this->assertInstanceOf(AuthResponseDTO::class, $result);
        $this->assertEquals('jwt-token-123', $result->access_token);
        $this->assertEquals('Bearer', $result->token_type);
        $this->assertEquals(config('jwt.ttl') * 60, $result->expires_in);
        $this->assertEquals($this->user, $result->user);
    }

    #[Test]
    public function it_throws_exception_when_registering_with_existing_email()
    {
        $registerDTO = new RegisterDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'password123'
        );

        $this->userRepositoryMock
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn($this->user);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User already exists with this email');

        $this->authService->register($registerDTO);
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

        /** @var TokenResponseDTO $result */
        $result = $this->authService->refresh();

        $this->assertInstanceOf(TokenResponseDTO::class, $result);
        $this->assertEquals('new-jwt-token-456', $result->access_token);
        $this->assertEquals('Bearer', $result->token_type);
        $this->assertEquals(config('jwt.ttl') * 60, $result->expires_in);
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
        $registerDTO = new RegisterDTO(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'plaintextpassword'
        );

        $this->roleRepositoryMock
            ->shouldReceive('findBySlug')
            ->once()
            ->with('user')
            ->andReturn(Role::factory()->makeOne(['slug' => 'user']));

        $this->userRepositoryMock
            ->shouldReceive('findByEmail')
            ->once()
            ->with('john@example.com')
            ->andReturn(null);

        $this->userRepositoryMock
            ->shouldReceive('createFromDTO')
            ->once()
            ->with(Mockery::on(function ($userDTO) {
                return $userDTO->first_name === 'John'
                    && $userDTO->last_name === 'Doe'
                    && $userDTO->email === 'john@example.com'
                    && Hash::check('plaintextpassword', $userDTO->password);
            }))
            ->andReturn($this->user);

        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->with($this->user)
            ->andReturn('jwt-token-123');

        /** @var AuthResponseDTO $result */
        $result = $this->authService->register($registerDTO);

        $this->assertEquals('jwt-token-123', $result->access_token);
    }
}
