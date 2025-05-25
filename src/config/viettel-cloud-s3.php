<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Viettel Cloud S3 Storage Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration is for the Viettel Cloud Object Storage service
    | which is compatible with VIPCore/EMC ViPR S3-compatible endpoints.
    | 
    | This driver solves signature compatibility issues between AWS SDK 
    | and VIPCore storage systems.
    |
    */

    'default' => [
        'driver' => 'viettel-s3',
        'key' => env('VIETTEL_S3_ACCESS_KEY_ID', env('AWS_ACCESS_KEY_ID')),
        'secret' => env('VIETTEL_S3_SECRET_ACCESS_KEY', env('AWS_SECRET_ACCESS_KEY')),
        'region' => env('VIETTEL_S3_REGION', env('AWS_DEFAULT_REGION', 'us-east-1')),
        'bucket' => env('VIETTEL_S3_BUCKET', env('AWS_BUCKET')),
        'url' => env('VIETTEL_S3_URL', env('AWS_URL')),
        'endpoint' => env('VIETTEL_S3_ENDPOINT', env('AWS_ENDPOINT')),
        'throw' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Example Configuration for Viettel Cloud Object Storage
    |--------------------------------------------------------------------------
    |
    | Here's an example configuration for the Viettel Cloud Object Storage.
    | Add these environment variables to your .env file:
    |
    | VIETTEL_S3_ACCESS_KEY_ID=your-access-key
    | VIETTEL_S3_SECRET_ACCESS_KEY=your-secret-key
    | VIETTEL_S3_REGION=us-east-1
    | VIETTEL_S3_BUCKET=your-bucket-name
    | VIETTEL_S3_ENDPOINT=https://vcos.cloudstorage.com.vn
    | VIETTEL_S3_URL=https://your-access-key.vcos.cloudstorage.com.vn/your-bucket-name
    |
    */

    'viettel_example' => [
        'driver' => 'viettel-s3',
        'key' => 'your-access-key',
        'secret' => 'your-secret-key',
        'region' => 'us-east-1',
        'bucket' => 'your-bucket-name',
        'url' => 'https://your-access-key.vcos.cloudstorage.com.vn/your-bucket-name',
        'endpoint' => 'https://vcos.cloudstorage.com.vn',
        'throw' => false,
    ],
]; 