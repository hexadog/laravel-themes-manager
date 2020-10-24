<?php

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
    | Symbolic path
    |--------------------------------------------------------------------------
    |
    | you can change the public themes path used for assets.
    |
    */
	'symlink_path' => 'themes',

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
		'enabled' => false,
		'key' => 'themes-manager',
		'lifetime' => 86400,
	],
];
