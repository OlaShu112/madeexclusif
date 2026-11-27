<?php

namespace App\Services\Finance;

use App\Models\User;
use App\Models\PlatformAdminPermission;
use App\Services\AuditService;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancePermissionService
{
    public function __construct(
        private AuditService $audit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Registry
    |--------------------------------------------------------------------------
    */

    public function available(): array
    {
        return config(
            'finance.permissions',
            []
        );
    }

    public function profiles(): array
    {
        return config(
            'finance.profiles',
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Check Permission
    |--------------------------------------------------------------------------
    */

    public function has(
        User $user,
        string $permission
    ): bool {

        if (!$user->isAdmin()) {
            return false;
        }

        if (
            !in_array(
                $permission,
                $this->available(),
                true
            )
        ) {
            return false;
        }

        return PlatformAdminPermission::query()
            ->forUser($user->id)
            ->active()
            ->where(
                'permission',
                $permission
            )
            ->exists();
    }

    public function hasAll(
        User $user,
        array $permissions
    ): bool {

        foreach ($permissions as $permission) {

            if (!$this->has($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    public function permissionsFor(
        User $user
    ): array {

        if (!$user->isAdmin()) {
            return [];
        }

        return PlatformAdminPermission::query()
            ->forUser($user->id)
            ->active()
            ->orderBy('permission')
            ->pluck('permission')
            ->filter(
                fn ($permission) => in_array(
                    $permission,
                    $this->available(),
                    true
                )
            )
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Grant Permission
    |--------------------------------------------------------------------------
    */

    public function grant(
        User $user,
        string $permission,
        User $actor
    ): PlatformAdminPermission {

        $this->authorizeManagement($actor);

        $this->validatePermission(
            $user,
            $permission
        );

        return DB::transaction(
            function () use (
                $user,
                $permission,
                $actor
            ) {

                $record =
                    PlatformAdminPermission::query()
                        ->updateOrCreate(
                            [
                                'user_id' => $user->id,

                                'permission' => $permission,
                            ],
                            [
                                'granted_by' => $actor->id,

                                'granted_at' => now(),

                                'expires_at' => null,
                            ]
                        );

                $this->audit->record(

                    action: 'finance.permission.granted',

                    userId: $actor->id,

                    entityType: 'user',

                    entityId: $user->id,

                    metadata: [

                        'permission' => $permission,

                        'outcome' => 'success',
                    ]
                );

                return $record;

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Revoke Permission
    |--------------------------------------------------------------------------
    */

    public function revoke(
        User $user,
        string $permission,
        User $actor
    ): bool {

        $this->authorizeManagement($actor);

        if (
            $permission === 'finance.permissions.manage'
            && $user->id === $actor->id
        ) {

            throw ValidationException::withMessages([

                'permission' =>
                    'You cannot revoke your own permission management access.',
            ]);
        }

        return DB::transaction(
            function () use (
                $user,
                $permission,
                $actor
            ) {

                $deleted =
                    PlatformAdminPermission::query()
                        ->forUser($user->id)
                        ->where(
                            'permission',
                            $permission
                        )
                        ->delete();

                if ($deleted > 0) {

                    $this->audit->record(

                        action: 'finance.permission.revoked',

                        userId: $actor->id,

                        entityType: 'user',

                        entityId: $user->id,

                        metadata: [

                            'permission' => $permission,

                            'outcome' => 'success',
                        ]
                    );
                }

                return $deleted > 0;

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assign Permission Profile
    |--------------------------------------------------------------------------
    */

    public function assignProfile(
        User $user,
        string $profile,
        User $actor
    ): array {

        $this->authorizeManagement($actor);

        $permissions = $this->profiles()[$profile]
            ?? null;

        if ($permissions === null) {

            throw ValidationException::withMessages([

                'profile' =>
                    'The selected Finance permission profile is invalid.',
            ]);
        }

        return DB::transaction(
            function () use (
                $user,
                $permissions,
                $actor
            ) {

                foreach ($permissions as $permission) {

                    $this->grant(
                        $user,
                        $permission,
                        $actor
                    );
                }

                return $this->permissionsFor($user);

            },
            3
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Internal Validation
    |--------------------------------------------------------------------------
    */

    private function authorizeManagement(
        User $actor
    ): void {

        abort_unless(

            $this->has(
                $actor,
                'finance.permissions.manage'
            ),

            403,

            'Finance permission management access required.'
        );
    }

    private function validatePermission(
        User $user,
        string $permission
    ): void {

        if (!$user->isAdmin()) {

            throw ValidationException::withMessages([

                'user_id' =>
                    'Finance permissions can only be assigned to administrators.',
            ]);
        }

        if (
            !in_array(
                $permission,
                $this->available(),
                true
            )
        ) {

            throw ValidationException::withMessages([

                'permission' =>
                    'The selected Finance permission is invalid.',
            ]);
        }
    }
}