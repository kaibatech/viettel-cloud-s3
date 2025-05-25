<?php

namespace Kaibatech\ViettelCloudS3;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter as LaravelFilesystemAdapter;

class ViettelCloudS3ServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/config/viettel-cloud-s3.php', 
            'viettel-cloud-s3'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/config/viettel-cloud-s3.php' => config_path('viettel-cloud-s3.php'),
        ], 'viettel-cloud-s3-config');

        // Register the custom storage driver
        Storage::extend('viettel-s3', function ($app, $config) {
            $adapter = new ViettelCloudS3Adapter($config);
            $filesystem = new Filesystem($adapter, $config);
            
            return new LaravelFilesystemAdapter($filesystem, $adapter, $config);
        });
    }
} 