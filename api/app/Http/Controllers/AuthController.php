
<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\Store;
use App\Models\User;
use App\Services\StoreCustomerService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    private const TOKEN_NAME = 'storefront';

    public function register(
        RegisterRequest $request,
        StoreCustomerService $customers
    ): JsonResponse {

        $store = $this->resolveBusiness(
            $request,
            $customers
        );

        $user = DB::transaction(function () use (
            $request,
            $store,
            $customers
        ) {
            $user = User::create(
                $request->safe()->only([
                    'name',
                    'email',
                    'password',
                ])
            );

            if ($store) {
                $customers->associate($store, $user);
            }

            return $user;
        });

        event(new Registered($user));

        return $this->tokenResponse($user, 201);
    }

    public function login(
        LoginRequest $request,
        StoreCustomerService $customers
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

        if ($portal === 'seller' && $user->isAdmin()) {
            abort(403, 'Please use the administrator portal.');
        }

        if ($portal === 'customer' && $user->isAdmin()) {
            abort(
                403,
                'Please sign in through the administrator portal.'
            );
        }

        if ($portal === 'customer') {

            $store = $this->resolveBusiness(
                $request,
                $customers
            );

            if ($store) {

                $customers->associate($store, $user);

                $customers->requireActiveCustomer(
                    $store,
                    $user
                );
            }
        }

        return $this->tokenResponse($user);
    }

    public function me(
        Request $request
    ): JsonResponse {
        return response()->json(
            new UserResource($request->user())
        );
    }

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

    private function resolveBusiness(
        Request $request,
        StoreCustomerService $customers
    ): ?Store {

        $data = $request->validate([
            'store' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        if (empty($data['store'])) {
            return null;
        }

        return $customers->resolveStore(
            $data['store']
        );
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
