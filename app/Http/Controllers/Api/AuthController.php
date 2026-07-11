<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Authentication Controller
 *
 * Handles all authentication-related endpoints:
 * - Login/Logout
 * - Me (Get the currently authenticated user)
 */
class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * User login
     *
     * Authenticates user and returns API token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $result = $this->authService->login($data);

            return respondWithData([
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'user' => new UserResource($result['user']),
            ], 'Login successful');
        } catch (\Exception $e) {
            return respondError($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * User logout
     *
     * Revokes the current API token
     */
    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout(Auth::user());

            return respondWithMessage('Logged out successfully');
        } catch (\Exception $e) {
            return respondError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get current authenticated user
     *
     * Returns user information if authenticated
     */
    public function me(): JsonResponse
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return respondError('Unauthenticated', Response::HTTP_UNAUTHORIZED);
            }

            return respondWithData(
                new UserResource($user),
                'User information retrieved successfully'
            );
        } catch (\Exception $e) {
            return respondError($e->getMessage(), Response::HTTP_UNAUTHORIZED);
        }
    }
}
