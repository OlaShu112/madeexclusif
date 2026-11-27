<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Services\Finance\FinancePermissionService;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FinancePermissionServiceTest extends FinanceTestCase
{
    private function service(): FinancePermissionService
    {
        return app(
            FinancePermissionService::class
        );
    }

    private function manager(): User
    {
        $admin = $this->createAdmin();

        $this->grantPermission(
            $admin,
            'finance.permissions.manage'
        );

        return $admin;
    }

    private function registeredPermission(): string
    {
        $permissions = $this->service()->available();

        $this->assertNotEmpty($permissions);

        return $permissions[0];
    }

    public function test_permission_can_be_granted(): void
    {
        $manager = $this->manager();

        $admin = $this->createAdmin();

        $permission = $this->registeredPermission();

        $record = $this->service()->grant(
            $admin,
            $permission,
            $manager
        );

        $this->assertSame(
            $admin->id,
            $record->user_id
        );

        $this->assertTrue(
            $this->service()->has(
                $admin,
                $permission
            )
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action' => 'finance.permission.granted',
                'user_id' => $manager->id,
                'entity_id' => $admin->id,
            ]
        );
    }

    public function test_duplicate_grant_updates_existing_record(): void
    {
        $manager = $this->manager();

        $admin = $this->createAdmin();

        $permission = $this->registeredPermission();

        $first = $this->service()->grant(
            $admin,
            $permission,
            $manager
        );

        $second = $this->service()->grant(
            $admin,
            $permission,
            $manager
        );

        $this->assertSame(
            $first->id,
            $second->id
        );
    }

    public function test_permission_can_be_revoked(): void
    {
        $manager = $this->manager();

        $admin = $this->createAdmin();

        $permission = $this->registeredPermission();

        $this->service()->grant(
            $admin,
            $permission,
            $manager
        );

        $this->assertTrue(
            $this->service()->revoke(
                $admin,
                $permission,
                $manager
            )
        );

        $this->assertFalse(
            $this->service()->has(
                $admin,
                $permission
            )
        );

        $this->assertDatabaseHas(
            'audit_logs',
            [
                'action' => 'finance.permission.revoked',
                'entity_id' => $admin->id,
            ]
        );
    }

    public function test_invalid_permission_is_rejected(): void
    {
        $manager = $this->manager();

        $admin = $this->createAdmin();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->grant(
            $admin,
            'finance.invalid.permission',
            $manager
        );
    }

    public function test_permission_cannot_be_granted_to_non_admin(): void
    {
        $manager = $this->manager();

        $user = User::factory()->create();

        $this->assertFalse($user->isAdmin());

        $this->expectException(
            ValidationException::class
        );

        $this->service()->grant(
            $user,
            $this->registeredPermission(),
            $manager
        );
    }

    public function test_unauthorised_admin_cannot_grant_permissions(): void
    {
        $actor = $this->createAdmin();

        $target = $this->createAdmin();

        $this->expectException(
            HttpException::class
        );

        $this->service()->grant(
            $target,
            $this->registeredPermission(),
            $actor
        );
    }

    public function test_manager_cannot_revoke_own_management_permission(): void
    {
        $manager = $this->manager();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->revoke(
            $manager,
            'finance.permissions.manage',
            $manager
        );
    }

    public function test_expired_permissions_are_excluded(): void
    {
        $admin = $this->createAdmin();

        $permission = $this->registeredPermission();

        $this->grantPermission(
            $admin,
            $permission,
            [
                'expires_at' => now()->subMinute(),
            ]
        );

        $this->assertFalse(
            $this->service()->has(
                $admin,
                $permission
            )
        );

        $this->assertNotContains(
            $permission,
            $this->service()->permissionsFor($admin)
        );
    }

    public function test_permission_profile_can_be_assigned(): void
    {
        $manager = $this->manager();

        $admin = $this->createAdmin();

        $profiles = $this->service()->profiles();

        $this->assertNotEmpty($profiles);

        $profile = array_key_first($profiles);

        $assigned = $this->service()->assignProfile(
            $admin,
            $profile,
            $manager
        );

        foreach ($profiles[$profile] as $permission) {

            $this->assertContains(
                $permission,
                $assigned
            );
        }
    }

    public function test_invalid_profile_is_rejected(): void
    {
        $manager = $this->manager();

        $admin = $this->createAdmin();

        $this->expectException(
            ValidationException::class
        );

        $this->service()->assignProfile(
            $admin,
            'invalid-profile',
            $manager
        );
    }

    public function test_has_all_permissions(): void
    {
        $admin = $this->createFinanceAdmin();

        $permissions = $this->service()->available();

        $this->assertNotEmpty($permissions);

        $this->assertTrue(
            $this->service()->hasAll(
                $admin,
                $permissions
            )
        );

        $this->assertFalse(
            $this->service()->hasAll(
                $admin,
                [
                    'finance.invalid.permission',
                ]
            )
        );
    }
}