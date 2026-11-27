<?php

namespace App\Services\Communication;

use App\Models\User;
use App\Models\PlatformAdminPermission;
use App\Services\AuditService;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CommunicationPermissionService
{
    public function __construct(
        private AuditService $audit
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | Available Permissions
    |--------------------------------------------------------------------------
    */

    public function available(): array
    {
        return config(
            'communication.permissions',
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

        if (!in_array(
            $permission,
            $this->available(),
            true
        )) {
            return false;
        }

        return PlatformAdminPermission::query()
            ->forUser($user->id)
            ->active()
            ->where('permission', $permission)
            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Check Multiple Permissions
    |--------------------------------------------------------------------------
    */

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

    public function hasAny(
        User $user,
        array $permissions
    ): bool {

        foreach ($permissions as $permission) {

            if ($this->has($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    /*
    |--------------------------------------------------------------------------
    | Retrieve Administrator Permissions
    |--------------------------------------------------------------------------
    */

    public function permissionsFor(
        User $user
    ): array {

        if (!$user->isAdmin()) {
            return [];
        }

        return PlatformAdminPermission::query()
            ->forUser($user->id)
            ->active()
            ->whereIn(
                'permission',
                $this->available()
            )
            ->orderBy('permission')
            ->pluck('permission')
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

        return DB::transaction(function () use (
            $user,
            $permission,
            $actor
        ) {

            $record = PlatformAdminPermission::query()
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

                action: 'communication.permission.granted',

                userId: $actor->id,

                entityType: 'user',

                entityId: $user->id,

                metadata: [
                    'permission' => $permission,
                    'outcome' => 'success',
                ]
            );

            return $record;

        }, 3);
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

        if (!in_array(
            $permission,
            $this->available(),
            true
        )) {

            throw ValidationException::withMessages([
                'permission' =>
                    'Invalid Communication permission.',
            ]);
        }

        if (
            $permission === 'communication.permissions.manage'
            && $user->id === $actor->id
        ) {

            throw ValidationException::withMessages([
                'permission' =>
                    'You cannot revoke your own permission management access.',
            ]);
        }

        return DB::transaction(function () use (
            $user,
            $permission,
            $actor
        ) {

            $deleted = PlatformAdminPermission::query()
                ->forUser($user->id)
                ->where('permission', $permission)
                ->delete();

            if ($deleted > 0) {

                $this->audit->record(

                    action: 'communication.permission.revoked',

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

        }, 3);
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Management Authorisation
    |--------------------------------------------------------------------------
    */

    private function authorizeManagement(
        User $actor
    ): void {

        abort_unless(

            $this->has(
                $actor,
                'communication.permissions.manage'
            ),

            403,

            'Communication permission management access required.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Permission Validation
    |--------------------------------------------------------------------------
    */

    private function validatePermission(
        User $user,
        string $permission
    ): void {

        if (!$user->isAdmin()) {

            throw ValidationException::withMessages([
                'user_id' =>
                    'Communication permissions can only be assigned to administrators.',
            ]);
        }

        if (!in_array(
            $permission,
            $this->available(),
            true
        )) {

            throw ValidationException::withMessages([
                'permission' =>
                    'The selected Communication permission is invalid.',
            ]);
        }
    }
}