<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use PNScripts\ProverkaLite\ProverkaServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [ProverkaServiceProvider::class];
    }
}
