<?php

namespace App\Services\System;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthService
{
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'platform_tables' => $this->checkTables(),
        ];

        $healthy = collect($checks)->every(
            fn ($check) => $check['healthy'] === true
        );

        return [
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'healthy' => $healthy,
            'checks' => $checks,
            'checked_at' => now()->toIso8601String(),
        ];
    }

    private function checkDatabase(): array
    {
        try {
            DB::select('SELECT 1');

            return [
                'healthy' => true,
                'message' => 'Database connection available.',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'healthy' => false,
                'message' => 'Database connection unavailable.',
            ];
        }
    }

    private function checkCache(): array
    {
        $key = 'ivision:health:' . Str::uuid();

        try {
            Cache::put($key, 'healthy', 30);

            $healthy = Cache::get($key) === 'healthy';

            Cache::forget($key);

            return [
                'healthy' => $healthy,
                'message' => $healthy
                    ? 'Cache available.'
                    : 'Cache verification failed.',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'healthy' => false,
                'message' => 'Cache unavailable.',
            ];
        }
    }

    private function checkStorage(): array
    {
        $path = 'health-checks/' . Str::uuid() . '.txt';

        try {
            $disk = Storage::disk('local');

            $written = $disk->put($path, 'healthy');

            $healthy = $written
                && $disk->get($path) === 'healthy';

            $disk->delete($path);

            return [
                'healthy' => $healthy,
                'message' => $healthy
                    ? 'Storage available.'
                    : 'Storage verification failed.',
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'healthy' => false,
                'message' => 'Storage unavailable.',
            ];
        }
    }

    private function checkTables(): array
    {
        $requiredTables = [
            'users',
            'stores',
            'products',
            'orders',
            'platform_admin_permissions',
            'platform_email_templates',
            'platform_communications',
            'platform_communication_recipients',
            'platform_announcements',
            'platform_notifications',
            'platform_communication_preferences',
            'platform_communication_attachments',
            'platform_automation_rules',
        ];

        try {
            $missing = [];

            foreach ($requiredTables as $table) {
                if (!Schema::hasTable($table)) {
                    $missing[] = $table;
                }
            }

            return [
                'healthy' => empty($missing),
                'required_tables' => count($requiredTables),
                'missing_tables' => $missing,
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'healthy' => false,
                'message' => 'Unable to verify platform tables.',
            ];
        }
    }
}