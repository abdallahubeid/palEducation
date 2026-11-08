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

        /*
         * Paid content: library files (and lecture assets, if any ever land
         * on our own disk). A name distinct from `local` so every call site
         * reads as "this is private", and deliberately WITHOUT 'serve' and
         * WITHOUT 'url': this disk registers no route and builds no public
         * link. Delivery is one controller that checks ownership + branch +
         * subscription, then response()->file().
         *
         * Media rows for these carry disk => 'private', and Media::url()
         * returns null for them by contract — nothing renders a raw link.
         */
        'private' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'visibility' => 'private',
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        /*
         * Public marketing assets uploaded from the admin panel
         * (settings images, the intro video). Root is public/ itself, so
         * store() returns a ROOT-RELATIVE path such as
         * "uploads/settings/abc.jpg" that asset() can render directly.
         *
         * This mirrors Media::url(), which already does asset($this->path)
         * — one URL convention for every public asset in the project.
         *
         * NOTE: paid content (lectures, library files) must NEVER use this
         * disk. It is served straight from the document root with no
         * permission check.
         */
        'custom' => [
            'driver' => 'local',
            'root' => public_path(''),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/'),
            'visibility' => 'public',
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
