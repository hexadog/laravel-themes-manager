<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Path to lookup theme
    |--------------------------------------------------------------------------
    |
    | The root path contains themes collections.
    |
    */
    'directory' => env('THEMES_DIR', 'themes'),

    /*
    |--------------------------------------------------------------------------
    | Symbolic link path
    |--------------------------------------------------------------------------
    |
    | you can change the public themes path used for assets.
    |
    */
    'symlink_path' => 'themes',

    /*
    |--------------------------------------------------------------------------
    | Symbolic link relative
    |--------------------------------------------------------------------------
    |
    | Determine if relative symlink should be used instead of absolute one.
    |
    */
    'symlink_relative' => false,

    /*
    |--------------------------------------------------------------------------
    | Fallback Theme
    |--------------------------------------------------------------------------
    |
    | If you don't set a theme at runtime (through middleware for example)
    | the fallback theme will be used automatically.
    |
    */
    'fallback_theme' => null,

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Config for caching feature.
    |
    */
    'cache' => [
        'enabled'  => false,
        'key'      => 'themes-manager',
        'lifetime' => 86400,
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer File Template
    |--------------------------------------------------------------------------
    |
    | Config for composer.json file, generated for new theme
    | If null then information will be asked at generation process
    | If not null, values will be used at generation process
    |
    */
    'composer' => [
        'vendor' => null,
        'author' => [
            'name'  => null,
            'email' => null,
        ],
    ],
];
