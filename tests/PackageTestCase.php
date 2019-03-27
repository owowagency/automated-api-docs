<?php

namespace Vendor\Package\Tests;

use Vendor\Package\ServiceProvider;
use Orchestra\Testbench\TestCase;

abstract class PackageTestCase extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [ServiceProvider::class];
    }
}
