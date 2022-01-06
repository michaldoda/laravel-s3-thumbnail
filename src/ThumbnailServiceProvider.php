<?php

namespace MichalDoda\LaravelS3Thumbnail;

use Illuminate\Support\ServiceProvider;

class ThumbnailServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 's3-thumbnail');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('s3-thumbnail.php'),
            ], 's3-thumbnail-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/s3-thumbnail'),
            ], 's3-thumbnail-views');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 's3-thumbnail');
    }
}
