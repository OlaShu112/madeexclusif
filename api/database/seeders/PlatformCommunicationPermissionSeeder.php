<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PlatformAdminPermission;
use App\Services\AuditService;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use RuntimeException;

class PlatformCommunicationPermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Administrator Account
        |--------------------------------------------------------------------------
        */

        $email = getenv(
            'IVISION_COMMUNICATION_ADMIN_EMAIL'
        );

        if (!$email) {

            throw new RuntimeException(
                'Set IVISION_COMMUNICATION_ADMIN_EMAIL before running this seeder.'
            );
        }

        $admin = User::query()
            ->where('email', $email)
            ->where('role', 'admin')
            ->first();

        if (!$admin || !$admin->isAdmin()) {

            throw new RuntimeException(
                'The specified administrator account was not found.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Configured Communication Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = config(
            'communication.permissions',
            []
        );

        if (empty($permissions)) {

            throw new RuntimeException(
                'Communication permissions have not been configured.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Initialise Administrator Permissions
        |--------------------------------------------------------------------------
        */

        $created = DB::transaction(function () use (
            $admin,
            $permissions
        ) {

            $created = 0;

            foreach ($permissions as $permission) {

                $record = PlatformAdminPermission::query()
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

                    $created++;

                    app(AuditService::class)->record(

                        action:
                            'communication.permission.initialized',

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

            return $created;

        }, 3);

        /*
        |--------------------------------------------------------------------------
        | Seeder Results
        |--------------------------------------------------------------------------
        */

        $this->command->info(
            'IVISION Communication permissions initialized.'
        );

        $this->command->info(
            'Administrator: ' . $admin->email
        );

        $this->command->info(
            'Configured permissions: '
            . count($permissions)
        );

        $this->command->info(
            'New permissions created: ' . $created
        );
    }
}