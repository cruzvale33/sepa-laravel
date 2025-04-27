<?php

namespace SepaLaravel\SepaLaravel\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use SepaLaravel\SepaLaravel\SepaLaravelServiceProvider;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app)
    {
        return [SepaLaravelServiceProvider::class];
    }
}
