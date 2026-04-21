<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserService extends BaseService
{
    /**
     * UserService constructor.
     *
     * @param UserRepository $userRepository
     */
    public function __construct(protected UserRepository $userRepository)
    {
        parent::__construct();

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
            return $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error creating user: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Login user
     *
     * @param array $data
     * @return User|null
     */
    public function login(array $data): ?User
    {
        $this->clearErrors();

        $user = $this->userRepository->findByEmail($data['email']);

        if (!$user || !Hash::check($data['password'], $user->password)) {
            $this->errors->add('email', 'Invalid credentials');
            return null;
        }

        return $user;
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
     * @param array $data
     * @return User|null
     */
    public function requestPasswordReset(array $data): void
    {
        $this->clearErrors();

        try {
            $status = Password::sendResetLink([
                'email' => $data['email']
            ]);

            if ($status !== Password::RESET_LINK_SENT) {
                $this->errors->add('email', __($status));
            }
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error requesting password reset');
        }
    }

    /**
     * Reset user password
     *
     * @param array $data
     * @return bool
     */
    public function resetPassword(array $data): void
    {
        $this->clearErrors();

        try {
            $status = Password::reset(
                $data,
                function ($user, $password) {
                    $this->userRepository->update($user, [
                        'password' => Hash::make($password),
                    ]);

                    $user->tokens()->delete();
                }
            );

            if ($status !== Password::PASSWORD_RESET) {
                $this->errors->add('token', __($status));
            }

        } catch (\Exception $e) {
            $this->errors->add('general', 'Error resetting password');
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

        if (!Hash::check($data['current_password'], $user->password)) {
            $this->errors->add('current_password', 'Current password is incorrect');
            return null;
        }

        try {
            $updatedUser = $this->userRepository->update($user, [
                'password' => Hash::make($data['password']),
            ]);

            $user->tokens()->delete();

            return $updatedUser;
        } catch (\Exception $e) {
            $this->errors->add('general', 'Error changing password');
            return null;
        }
    }
}
