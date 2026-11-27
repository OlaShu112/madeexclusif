<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Contracts\Console\Kernel;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $connection = $app['config']->get(
            'database.default'
        );

        $database = $app['config']->get(
            "database.connections.{$connection}.database"
        );

        $username = $app['config']->get(
            "database.connections.{$connection}.username"
        );

        $databaseUrl = $app['config']->get(
            "database.connections.{$connection}.url"
        );

        if (!$app->environment('testing')) {
            throw new RuntimeException(
                'Tests must run in the testing environment.'
            );
        }

        if ($connection !== 'mysql') {
            throw new RuntimeException(
                'IVISION tests require the MySQL connection.'
            );
        }

        if ($database !== 'ivision_testing') {
            throw new RuntimeException(
                'Unsafe database detected. Testing aborted.'
            );
        }

        if ($username !== 'ivision_test') {
            throw new RuntimeException(
                'Tests must use the dedicated testing account.'
            );
        }

        if (!empty($databaseUrl)) {
            throw new RuntimeException(
                'Database URL overrides are prohibited during testing.'
            );
        }

        return $app;
    }
}