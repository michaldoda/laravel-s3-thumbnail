<?php

namespace MichalDoda\LaravelS3Thumbnail\Tests;

use Intervention\Image\ImageServiceProvider;
use MichalDoda\LaravelS3Thumbnail\ThumbnailServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ThumbnailServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app->addDeferredServices([
            ImageServiceProvider::class,
        ]);

//        it does not work https://github.com/orchestral/testbench/issues/79
//        $app->useEnvironmentPath(base_path());
//        $app->loadEnvironmentFrom(base_path('.env.testing'));
    }
}