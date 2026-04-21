<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Mockery;

class UserServiceTest extends TestCase
{
    protected $userRepository;
    protected $userService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = Mockery::mock(UserRepository::class);
        $this->userService = new UserService($this->userRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_registers_a_user_successfully()
    {
        // Given
        $data = [
            'name' => 'Josue',
            'email' => 'test@test.com',
            'password' => '123456'
        ];

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->andReturn(new User($data));

        // When
        $result = $this->userService->register($data);

        // Then
        $this->assertInstanceOf(User::class, $result);
    }

    public function test_it_returns_null_when_register_fails()
    {
        // Given
        $data = [
            'name' => 'Josue',
            'email' => 'test@test.com',
            'password' => '123456'
        ];

        $this->userRepository
            ->shouldReceive('create')
            ->once()
            ->andThrow(new \Exception('fail'));

        // When
        $result = $this->userService->register($data);

        // Then
        $this->assertNull($result);
        $this->assertTrue($this->userService->hasErrors());
    }

    public function test_it_logs_in_user_successfully()
    {
        // Given
        $user = new User([
            'email' => 'test@test.com',
            'password' => Hash::make('123456')
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        // When
        $result = $this->userService->login([
            'email' => 'test@test.com',
            'password' => '123456'
        ]);

        // Then
        $this->assertInstanceOf(User::class, $result);
    }

    public function test_it_fails_login_with_invalid_credentials()
    {
        // Given
        $user = new User([
            'email' => 'test@test.com',
            'password' => Hash::make('123456')
        ]);

        $this->userRepository
            ->shouldReceive('findByEmail')
            ->once()
            ->andReturn($user);

        // When
        $result = $this->userService->login([
            'email' => 'test@test.com',
            'password' => 'wrong'
        ]);

        // Then
        $this->assertNull($result);
        $this->assertTrue($this->userService->hasErrors());
    }

    public function test_it_gets_user_by_id()
    {
        // Given
        $user = new User(['id' => 1]);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($user);

        // When
        $result = $this->userService->getById(1);

        // Then
        $this->assertEquals($user, $result);
    }

    public function test_it_updates_user_successfully()
    {
        // Given
        $user = new User(['id' => 1]);

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn($user);

        // When
        $result = $this->userService->update($user, ['name' => 'Updated']);

        // Then
        $this->assertInstanceOf(User::class, $result);
    }

    public function test_it_changes_password_successfully()
    {
        // Given
        $user = Mockery::mock(User::class)->makePartial();
        $user->password = Hash::make('old');

        $user->shouldReceive('tokens->delete')->once();

        $this->userRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn($user);

        // When
        $result = $this->userService->changePassword($user, [
            'current_password' => 'old',
            'password' => 'new123'
        ]);

        // Then
        $this->assertInstanceOf(User::class, $result);
    }

    public function test_it_fails_change_password_when_current_password_is_wrong()
    {
        // Given
        $user = new User([
            'password' => Hash::make('correct')
        ]);

        // When
        $result = $this->userService->changePassword($user, [
            'current_password' => 'wrong',
            'password' => 'new'
        ]);

        // Then
        $this->assertNull($result);
        $this->assertTrue($this->userService->hasErrors());
    }

    public function test_it_requests_password_reset_successfully()
    {
        // Given
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::RESET_LINK_SENT);

        // When
        $this->userService->requestPasswordReset([
            'email' => 'test@test.com'
        ]);

        // Then
        $this->assertFalse($this->userService->hasErrors());
    }

    public function test_it_handles_reset_password_failure()
    {
        // Given
        Password::shouldReceive('reset')
            ->once()
            ->andReturn('error');

        // When
        $this->userService->resetPassword([
            'email' => 'test@test.com',
            'password' => '123',
            'token' => 'token'
        ]);

        // Then
        $this->assertTrue($this->userService->hasErrors());
    }
}
