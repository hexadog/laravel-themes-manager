<?php

namespace Hexadog\ThemesManager\Providers;

use File;
use ReflectionClass;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Hexadog\ThemesManager\ThemesManager;
use Hexadog\ThemesManager\Http\Middleware;
use Hexadog\ThemesManager\Console\Commands;
use Hexadog\ThemesManager\Console\Generators;
use Illuminate\Contracts\Translation\Translator;
use Hexadog\ThemesManager\Facades\ThemesManager as ThemesManagerFacade;

class PackageServiceProvider extends ServiceProvider
{
	/**
	 * Our root directory for this package to make traversal easier
	 */
	const PACKAGE_DIR = __DIR__ . '/../../';

	/**
	 * Name for this package to publish assets
	 */
	const PACKAGE_NAME = 'themes-manager';

	/**
	 * Pblishers list
	 */
	protected $publishers = [];

	/**
	 * Get Package absolute path
	 *
	 * @param string $path
	 * @return void
	 */
	protected function getPath($path = '')
	{
		// We get the child class
		$rc = new ReflectionClass(get_class($this));

		return dirname($rc->getFileName()) . '/../../' . $path;
	}

	/**
	 * Get Module normalized namespace
	 *
	 * @return void
	 */
	protected function getNormalizedNamespace($prefix = '')
	{
		return Str::start(Str::lower(self::PACKAGE_NAME), $prefix);
	}

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot(Router $router)
	{
		$publicPath = public_path(config('themes-manager.symlink_path', 'themes'));

		if (!File::exists($publicPath)) {
			app(Filesystem::class)->makeDirectory($publicPath);
		}
		
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
			return new ThemesManager(
				app(Factory::class),
				app(Filesystem::class),
				app(Translator::class)
			);
		});

		AliasLoader::getInstance()->alias('ThemesManager', ThemesManagerFacade::class);
		AliasLoader::getInstance()->alias('Theme', ThemesManagerFacade::class);

		$this->app->register(BladeServiceProvider::class);
	}

	/**
	 * Bootstrap our Configs
	 */
	protected function registerConfigs()
	{
		$configPath = $this->getPath('config');
		
		$this->mergeConfigFrom(
			"{$configPath}/config.php",
			$this->getNormalizedNamespace()
		);
	}

	protected function strapCommands()
	{
		if ($this->app->runningInConsole() || config('app.env') == 'testing') {
			$this->commands([
				Commands\ListThemes::class,
				Generators\MakeTheme::class,
			]);
		}
	}

	/**
	 * Bootstrap our Publishers
	 */
	protected function strapPublishers()
	{
		$configPath = $this->getPath('config');

		$this->publishes([
			"{$configPath}/config.php" => config_path($this->getNormalizedNamespace() . '.php'),
		], 'config');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [ThemesManager::class];
	}
}
