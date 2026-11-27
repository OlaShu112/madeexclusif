<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PlatformAdminPermission;
use App\Services\AuditService;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use RuntimeException;

class PlatformFinancePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $email = getenv(
            'IVISION_FINANCE_ADMIN_EMAIL'
        );

        if (!$email) {

            throw new RuntimeException(
                'Set IVISION_FINANCE_ADMIN_EMAIL before running this seeder.'
            );
        }

        $admin = User::query()
            ->where('email', $email)
            ->where('role', 'admin')
            ->first();

        if (!$admin) {

            throw new RuntimeException(
                'The specified administrator account was not found.'
            );
        }

        $permissions = config(
            'finance.permissions',
            []
        );

        if (empty($permissions)) {

            throw new RuntimeException(
                'Finance permissions have not been configured.'
            );
        }

        DB::transaction(
            function () use (
                $admin,
                $permissions
            ) {

                foreach ($permissions as $permission) {

                    $record =
                        PlatformAdminPermission::query()
                            ->firstOrCreate(

                                [

                                    'user_id' => $admin->id,

                                    'permission' => $permission,
                                ],

                                [

                                    'granted_by' => null,

                                    'granted_at' => now(),

                                    'expires_at' => null,
                                ]
                            );

                    if ($record->wasRecentlyCreated) {

                        app(AuditService::class)->record(

                            action:
                                'finance.permission.initialized',

                            entityType: 'user',

                            entityId: $admin->id,

                            metadata: [

                                'permission' => $permission,

                                'outcome' => 'success',

                                'source' => 'database_seeder',
                            ]
                        );
                    }
                }
            }
        );

        $this->command->info(
            'IVISION Finance permissions initialized.'
        );

        $this->command->info(
            'Administrator: ' . $admin->email
        );

        $this->command->info(
            'Configured permissions: '
            . count($permissions)
        );
    }
}