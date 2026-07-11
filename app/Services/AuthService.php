<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * Authenticate user and create API token
     *
     * @param  array{email?: string|null, country_code?: string|null, phone_number?: string|null, password: string}  $credentials
     * @return array<string, mixed>
     *
     * @throws \Exception If credentials are invalid or user is not active/verified
     */
    public function login(array $credentials): array
    {
        $password = $credentials['password'];

        $user = User::firstWhere('email', $credentials['email']);

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new \Exception('Invalid credentials');
        }

        if (! $user->is_active) {
            throw new \Exception('Your account is not active');
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'token' => $token,
            'user' => $user,
        ];
    }

    /**
     * Logout user by revoking current token
     */
    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }
    }

    /**
     * Get authenticated user data for the `me` endpoint.
     */
    public function getMe(User $user): User
    {
        return $user;
    }
}
