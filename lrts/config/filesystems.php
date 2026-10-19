<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'private' => [
            'driver' => env('PRIVATE_DISK_DRIVER', 'local'),
            'key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'), 'bucket' => env('PRIVATE_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'), 'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'root' => env('PRIVATE_DISK_DRIVER', 'local') === 'local' ? storage_path('app/private') : env('PRIVATE_STORAGE_PREFIX', ''),
            'serve' => false,
            'throw' => true,
            'report' => false,
        ],

        'legacy-private' => ['driver' => 'local', 'root' => storage_path('app/private'), 'throw' => true],
        'legacy-public' => ['driver' => 'local', 'root' => storage_path('app/public'), 'throw' => true],
        'public' => [
            'driver' => env('PUBLIC_DISK_DRIVER', 'local'),
            'key' => env('AWS_ACCESS_KEY_ID'), 'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'), 'bucket' => env('PUBLIC_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'), 'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'root' => env('PUBLIC_DISK_DRIVER', 'local') === 'local' ? storage_path('app/public') : env('PUBLIC_STORAGE_PREFIX', ''),
            'url' => env('PUBLIC_STORAGE_URL') ?: (env('PUBLIC_DISK_DRIVER', 'local') === 'local' ? rtrim(env('APP_URL', 'http://localhost'), '/').'/storage' : null),
            'visibility' => env('PUBLIC_DISK_DRIVER', 'local') === 'local' ? 'public' : env('PUBLIC_DISK_VISIBILITY', 'private'),
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
