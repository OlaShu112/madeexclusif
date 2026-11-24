<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'storefront';

    /**
     * Register a customer account.
     */
    public function register(
        RegisterRequest $request
    ): JsonResponse {
        $user = User::create(
            $request->safe()->only([
                'name',
                'email',
                'password',
            ])
        );

        event(new Registered($user));

        return $this->tokenResponse($user, 201);
    }

    /**
     * Exchange valid credentials for an API token.
     */
    public function login(
        LoginRequest $request
    ): JsonResponse {
        return $this->tokenResponse(
            $request->authenticate()
        );
    }

    /**
     * Return the authenticated user.
     */
    public function me(
        Request $request
    ): JsonResponse {
        return response()->json(
            new UserResource($request->user())
        );
    }

    /**
     * Revoke the current authentication token.
     */
    public function logout(
        Request $request
    ): JsonResponse {
        $request->user()
            ->currentAccessToken()
            ->delete();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    private function tokenResponse(
        User $user,
        int $status = 200
    ): JsonResponse {
        return response()->json([
            'user' => new UserResource($user),

            'token' => $user
                ->createToken(self::TOKEN_NAME)
                ->plainTextToken,
        ], $status);
    }
}