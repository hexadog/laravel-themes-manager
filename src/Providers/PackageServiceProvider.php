<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Providers;

use Hexadog\ThemesManager\Components\Image;
use Hexadog\ThemesManager\Components\PageTitle;
use Hexadog\ThemesManager\Components\Script;
use Hexadog\ThemesManager\Components\Style;
use Hexadog\ThemesManager\Console\Commands;
use Hexadog\ThemesManager\Console\Generators;
use Hexadog\ThemesManager\Facades\ThemesManager as ThemesManagerFacade;
use Hexadog\ThemesManager\Http\Middleware;
use Hexadog\ThemesManager\ThemesManager;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class PackageServiceProvider extends ServiceProvider
{
    /**
     * Name for this package to publish assets.
     */
    protected const PACKAGE_NAME = 'themes-manager';

    /**
     * Pblishers list.
     */
    protected $publishers = [];

    /**
     * Bootstrap the application events.
     */
    public function boot(Router $router): void
    {
        $this->loadViewsFrom($this->getPath('resources/views'), 'themes-manager');
        $this->loadViewComponentsAs('theme', [
            Image::class,
            PageTitle::class,
            Script::class,
            Style::class,
        ]);

        $this->strapPublishers();
        $this->strapCommands();

        $router->aliasMiddleware('theme', Middleware\ThemeLoader::class);
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->registerConfigs();

        $this->app->singleton('themes-manager', function () {
            return new ThemesManager;
        });

        AliasLoader::getInstance()->alias('ThemesManager', ThemesManagerFacade::class);
        AliasLoader::getInstance()->alias('Theme', ThemesManagerFacade::class);

        $this->app->register(BladeServiceProvider::class);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [ThemesManager::class];
    }

    /**
     * Get Package absolute path.
     */
    protected function getPath(string $path = '')
    {
        // We get the child class
        $rc = new \ReflectionClass(static::class);

        return dirname($rc->getFileName()) . '/../../' . $path;
    }

    /**
     * Get Module normalized namespace.
     */
    protected function getNormalizedNamespace(mixed $prefix = '')
    {
        return Str::start(Str::lower(self::PACKAGE_NAME), $prefix);
    }

    /**
     * Bootstrap our Configs.
     */
    protected function registerConfigs(): void
    {
        $configPath = $this->getPath('config');

        $this->mergeConfigFrom(
            "{$configPath}/config.php",
            $this->getNormalizedNamespace()
        );
    }

    protected function strapCommands(): void
    {
        if ($this->app->runningInConsole() || config('app.env') === 'testing') {
            $this->commands([
                Commands\ClearCache::class,
                Commands\ListThemes::class,
                Generators\MakeTheme::class,
            ]);
        }
    }

    /**
     * Bootstrap our Publishers.
     */
    protected function strapPublishers(): void
    {
        $configPath = $this->getPath('config');

        $this->publishes([
            "{$configPath}/config.php" => config_path($this->getNormalizedNamespace() . '.php'),
        ], 'config');

        $this->publishes([
            $this->getPath('resources/views') => resource_path('views/vendor/themes-manager'),
        ], 'views');
    }
}
