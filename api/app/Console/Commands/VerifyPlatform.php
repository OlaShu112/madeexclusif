<?php

namespace App\Console\Commands;

use App\Services\System\SystemHealthService;
use Illuminate\Console\Command;

class VerifyPlatform extends Command
{
    protected $signature = 'ivision:verify';

    protected $description =
        'Verify essential IVISION platform dependencies';

    public function handle(
        SystemHealthService $health
    ): int {
        $this->info('IVISION Platform Verification');

        $result = $health->check();

        $rows = [];

        foreach ($result['checks'] as $name => $check) {
            $rows[] = [
                $name,
                $check['healthy'] ? 'PASS' : 'FAIL',
                $check['message']
                    ?? implode(
                        ', ',
                        $check['missing_tables'] ?? []
                    ),
            ];
        }

        $this->table([
            'Component',
            'Status',
            'Details',
        ], $rows);

        if (!$result['healthy']) {
            $this->error(
                'Platform dependency verification failed.'
            );

            return self::FAILURE;
        }

        $this->info(
            'All configured dependency checks passed.'
        );

        return self::SUCCESS;
    }
}