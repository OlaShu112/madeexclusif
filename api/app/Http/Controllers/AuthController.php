<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'storefront';

    /**
     * Register a customer account and sign them in.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Only validated fields are used, so a client can never set its own role.
        $user = User::create($request->safe()->only(['name', 'email', 'password']));

        return $this->tokenResponse($user, 201);
    }

    /**
     * Exchange valid credentials for an API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->tokenResponse($request->authenticate());
    }

    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

    /**
     * Revoke the token used for this request.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken(self::TOKEN_NAME)->plainTextToken,
        ], $status);
    }
}