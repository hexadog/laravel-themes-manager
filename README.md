<p align="center">Laravel Themes Manager</p>

<p align="center">
    <a href="https://packagist.org/packages/hexadog/laravel-themes-manager">
        <img src="https://poser.pugx.org/hexadog/laravel-themes-manager/v" alt="Latest Stable Version">
    </a>
    <a href="https://packagist.org/packages/hexadog/laravel-themes-manager">
        <img src="https://poser.pugx.org/hexadog/laravel-themes-manager/downloads" alt="Total Downloads">
    </a>
    <a href="https://packagist.org/packages/hexadog/laravel-themes-manager">
        <img src="https://poser.pugx.org/hexadog/laravel-themes-manager/license" alt="License">
    </a>
</p>

## Introduction
<code>hexadog/laravel-themes-manager</code> is a Laravel package which was created to let you developing multi-themes Laravel application.

## Installation
This package requires PHP 7.3 and Laravel 7.0 or higher.

To get started, install Themes Manager using Composer:
```shell
composer require hexadog/laravel-themes-manager
```

The package will automatically register its service provider.

To publish the config file to config/themes-manager.php run:
```shell
php artisan vendor:publish --provider="Hexadog\ThemesManager\Providers\PackageServiceProvider"
```

## Configuration
This is the default contents of the configuration:
```php
<?php

return [
	/*
	|--------------------------------------------------------------------------
	| Path to lookup theme
	|--------------------------------------------------------------------------
	|
	| The root path containing themes collection.
	|
	*/
	'directory' => env('THEMES_DIR', 'themes'),
    
    /*
    |--------------------------------------------------------------------------
    | Symbolic path
    |--------------------------------------------------------------------------
    |
    | you can change the public themes path used for assets
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
];
```

## Usage
There is multiple ways to work with Themes Manager. You can either set a new theme manually, using Web Middleware or Route Middleware.

### Manually Set Theme
Use one of the following method to set a theme any time:
```php
ThemesManager::set('one');

// or
Theme::set('one');
```

### Web Middleware
Create a new Middleware in <code>app/Http/Middleware</code> and configure your Laravel application to use it. This middleware must extends <code>Hexadog\ThemesManager\Http\Middleware\ThemeLoader</code> middleware.

Here is an example of middleware which set a theme based on the request url. It activated `admin` theme if current request url matches the `http(s)://mydomain/admin` pattern and use the fallback theme otherwise.
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Hexadog\ThemesManager\Http\Middleware\ThemeLoader as HexadogThemeLoader;

class ThemeLoader extends HexadogThemeLoader
{
    public function handle($request, Closure $next)
    {
        // Check if request url starts with admin prefix
		if (!is_null(Request()->getPathInfo()) && Str::startsWith(ltrim(Request()->getPathInfo(), '/'), 'admin') {
            // Set a specific theme for matching urls
            $theme = 'backend';
        }

		// Call parent Middleware handle method
        parent::handle($request, $next, $theme);
    }
}
```

Edit <code>App\Http\Kernel.php</code> file to add your new middleware into the list of middlewares used by your application.
```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        // ...
        \App\Http\Middleware\ThemeLoader::class,
	];
	
	// ...
}
```

## Route Middleware
Edit App\Http\Kernel.php file to add the route middleware into the list of middlewares used by your application.
```php
// Within App\Http\Kernel Class...

protected $routeMiddleware = [
    // ...
    'theme' => \Hexadog\ThemesManager\Http\Middleware\ThemeLoader::class,
];
```

Once the middleware has been defined in the HTTP kernel, you can use the middleware method to assign a theme to a route (or a group of routes):
```php
// Use theme named "one" for set of routes
Route::middleware('theme:one')->group(function() {
    // Your routes definition here
});

// Use Theme names "two" for another set of routes
Route::middleware('theme:two')->group(function() {
    // Your routes definition here
});
```

## Assets
A theme can have its own assets (images, stylesheets, javascript, ...). Theme's specific assets should be stored within <code>themes/themeVendor/themeName/public</code> folder of the theme.

When a theme is activted, this directory is linked (using symbolic link) into <code>public/themes</code> folder of the Laravel application so assets will be available publicly.

### Asset url
Ask the theme manager to generate the stylesheet HTML tag:
```php
{!! Theme::asset('css/app.min.css') !!}

// or using helper
{!! theme_asset('css/app.min.css') !!}
```

This call will return the url of requested asset:
```
/themes/hexadog/default/css/app.min.css
```

### Style
Ask the theme manager to generate the stylesheet HTML tag:
```php
{!! Theme::style('css/app.min.css') !!}

// or using helper
{!! theme_style('css/app.min.css') !!}
```

This call will generate the following code:
```html
<link href="/themes/hexadog/default/css/app.min.css">
```

### Script
Ask the theme manager to generate the script HTML tag:
```php
{!! Theme::script('js/app.min.js') !!}

// or using helper
{!! theme_script('js/app.min.js') !!}
```

This call will generate the following code:
```html
<script src="/themes/hexadog/default/js/app.min.js"></script>
```

### Image
Ask the theme manager to generate the image HTML tag:
```php
{!! Theme::image('img/logo.png', 'My Theme logo') !!}

// or using helper
{!! theme_image('img/logo.png', 'My Theme logo') !!}
```
This call will generate the following code:
```html
<img src="/themes/hexadog/default/img/logo.png" alt="My Theme logo" />
```

## Blade Directives
This package provides some blade helpers:

### Display page title
```php
@pagetitle('My page') // display "My page - Site Name"

// Only display page title without site name
@pagetitle('My page', false) // display "My page"

// Customize page title, site name separator
@pagetitle('My page', true, ' | ') // display "My page | Site Name"
```

## Artisan Command
This package provides some artisan commands in order to manage themes.

### Create Theme
You can easily create a new Theme by using the following command and follow the steps:
```shell
php artisan theme:make
```

This command will create a new Theme directory with all necessary files within the `themes` folder.

### List Themes
List all existing themes in your application with their details.
```shell
php artisan theme:list
```

## View
Themes Manager will prepend theme views paths to the existing Laravel View Finder locations. This way you can easily override any default view (even any third package published views).

Suppose you request to display `welcome.blade.php` view
```php
return view('welcome');
```

1. View will be searched into the current active theme `resources/views` folder 
2. If the view is not found in active theme then search into parents themes recursively
3. If the view is still not found then search laravel default view folder `resources/views`

## Related projects
- [Laravel Theme Installer](https://github.com/hexadog/laravel-theme-installer): Composer plugin to install `laravel-theme` packages outside vendor directory .

## License

Laravel Themes Manager is open-sourced software licensed under the [MIT license](LICENSE).