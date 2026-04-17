<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class UserService extends BaseService
{
    /**
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * UserService constructor.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    /**
     * Register a new user
     *
     * @param array $data
     * @return User|null
     */
    public function register(array $data): ?User
    {
        try {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return $user;
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error creating user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Login user
     *
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function login(string $email, string $password): ?User
    {
        $this->clearErrors();

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors->add('email', 'Invalid email format');
            return null;
        }

        $user = $this->userRepository->findByEmail($email);

        if (!$user || !Hash::check($password, $user->password)) {
            $this->errors->add('email', 'Invalid credentials');
            return null;
        }

        return $user;
    }

    /**
     * Get user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function getById(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }

    /**
     * Get user by email
     *
     * @param string $email
     * @return User|null
     */
    public function getByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    /**
     * Update user
     *
     * @param User $user
     * @param array $data
     * @return User|null
     */
    public function update(User $user, array $data): ?User
    {
        $this->clearErrors();

        try {
            return $this->userRepository->update($user, $data);
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error updating user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Change user password
     *
     * @param User $user
     * @param array $data
     * @return User|null
     */
    public function changePassword(User $user, array $data): ?User
    {
        $this->clearErrors();

        // Validate input
        $validator = Validator::make($data, [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            $this->errors = $validator->errors();
            return null;
        }

        // Verify current password
        if (!Hash::check($data['current_password'], $user->password)) {
            $this->errors->add('current_password', 'Current password is incorrect');
            return null;
        }

        try {
            return $this->userRepository->update($user, [
                'password' => Hash::make($data['password']),
            ]);
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error changing password: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Request password reset
     *
     * @param string $email
     * @return bool
     */
    public function requestPasswordReset(string $email): bool
    {
        $this->clearErrors();

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors->add('email', 'Invalid email format');
            return false;
        }

        try {
            Password::sendResetLink(['email' => $email]);
            return true;
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error requesting password reset: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset user password
     *
     * @param array $data
     * @return bool
     */
    public function resetPassword(array $data): bool
    {
        $this->clearErrors();

        // Validate input
        $validator = Validator::make($data, [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            $this->errors = $validator->errors();
            return false;
        }

        try {
            $response = Password::reset($data, function ($user, $password) {
                $this->userRepository->update($user, [
                    'password' => Hash::make($password),
                ]);
            });

            return $response === Password::PASSWORD_RESET;
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error resetting password: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete user
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        $this->clearErrors();

        try {
            return $this->userRepository->delete($user);
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error deleting user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate email format
     *
     * @param string $email
     * @return bool
     */
    public function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email);
    }
}
