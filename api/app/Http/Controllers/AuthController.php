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
        $portal = $request->validate([
            'portal' => [
                'nullable',
                'in:customer,admin,seller',
            ],
        ])['portal'] ?? 'customer';

        $user = $request->authenticate();

        if ($portal === 'admin' && ! $user->isAdmin()) {
            abort(403, 'Administrator access is required.');
        }

        if ($portal === 'seller') {

            if ($user->isAdmin()) {
                abort(403, 'Please use the administrator portal.');
            }

            // Business applicants may authenticate to
            // complete onboarding or track their application.
            // Protected seller operations remain restricted
            // by the EnsureSeller middleware.
        }

        if ($portal === 'customer' && $user->isAdmin()) {
            abort(403, 'Please sign in through the administrator portal.');
        }

        return $this->tokenResponse($user);
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