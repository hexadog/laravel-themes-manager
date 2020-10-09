<?php

namespace Hexadog\ThemesManager;

use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Hexadog\ThemesManager\Events\ThemeEnabled;
use Hexadog\ThemesManager\Events\ThemeDisabled;
use Hexadog\ThemesManager\Events\ThemeEnabling;
use Hexadog\ThemesManager\Traits\ComposerTrait;
use Hexadog\ThemesManager\Events\ThemeDisabling;

class Theme
{
	use ComposerTrait;

	/**
	 * The theme path.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * The theme assets path (within public storage).
	 *
	 * @var string
	 */
	protected $assetsPath;

	/**
	 * The Parent theme.
	 *
	 * @var string
	 */
	protected $parent = null;

	/**
	 * The theme status (enabled or not).
	 *
	 * @var string
	 */
	protected $status = false;

	/**
	 * The constructor.
	 *
	 * @param Container $app
	 * @param $name
	 * @param $path
	 */
	public function __construct($path)
	{
		$this->setPath($path);
	}

	/**
	 * Get path.
	 *
	 * @return string
	 */
	public function getPath(string $path = null): string
	{
		return $this->path . $this->cleanPath($path);
	}

	/**
	 * Get assets path.
	 *
	 * @return string
	 */
	public function getAssetsPath(string $path = null): string
	{
		return Config::get('themes-manager.symlink_path', 'themes') . DIRECTORY_SEPARATOR . mb_strtolower($this->getName()) . DIRECTORY_SEPARATOR . $this->cleanPath($path);
	}

	/**
	 * Set path.
	 *
	 * @param string $path
	 *
	 * @return $this
	 */
	public function setPath($path)
	{
		$this->path = $this->cleanPath($path);

		return $this;
	}

	/**
	 * Get theme views paths
	 *
	 * @param string $path
	 * @return void
	 */
	public function getViewPaths($path = '')
	{
		// Build Paths array.
		// All paths are relative to Config::get('themes-manager.directory')
		$paths = [];
		$theme = $this;

		do {
			$viewsPath = $theme->getPath('resources/views' . ($path ? "/{$path}" : ''));

			if (!in_array($viewsPath, $paths)) {
				$paths[] = $viewsPath;
			}
		} while ($theme = $theme->getParent());

		return $paths;
	}

	/**
	 * Check if has parent Theme
	 *
	 * @return boolean
	 */
	public function hasParent(): bool
	{
		return !is_null($this->parent);
	}

	/**
	 * Set parent Theme
	 *
	 * @param Theme $theme
	 * @return void
	 */
	public function setParent(Theme $theme)
	{
		$this->parent = $theme;
	}

	/**
	 * Get parent Theme
	 * 
	 * @return Theme|null
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * Determine whether the given status same with the current theme status.
	 *
	 * @param $status
	 *
	 * @return bool
	 */
	public function setStatus(bool $status): Theme
	{
		$this->status = $status;

		return $this;
	}

	/**
	 * Determine whether the given status same with the current module status.
	 *
	 * @param $status
	 *
	 * @return bool
	 */
	public function isStatus(bool $status = false): bool
	{
		return $this->get('extra.theme.active', false) === $status;
	}

	/**
	 * Determine whether the current theme activated.
	 *
	 * @return bool
	 */
	public function enabled(): bool
	{
		return $this->isStatus(true);
	}

	/**
	 *  Determine whether the current theme not disabled.
	 *
	 * @return bool
	 */
	public function disabled(): bool
	{
		return !$this->enabled();
	}

	/**
	 * Disable the current theme
	 * 
	 * @return Theme
	 */
	public function disable(bool $withEvent = true): Theme
	{
		if ($withEvent) {
			event(new ThemeDisabling($this->getName()));
		}

		$this->setStatus(false);

		if ($withEvent) {
			event(new ThemeDisabled($this->getName()));
		}

		return $this;
	}

	/**
	 * Enable the current theme
	 * 
	 * @return Theme
	 */
	public function enable(bool $withEvent = true): Theme
	{
		if ($withEvent) {
			event(new ThemeEnabling($this->getName()));
		}

		$this->setStatus(true);
		$this->registerViews();

		if ($withEvent) {
			event(new ThemeEnabled($this->getName()));
		}

		return $this;
	}

	/**
	 * Get theme asset url
	 *
	 * @param string $url
	 * @param boolean $absolutePath
	 * 
	 * @return string|null
	 */
	public function url($url, $absolutePath = false): ?string
	{
		$url = ltrim($url, DIRECTORY_SEPARATOR);

		// return external URLs unmodified
		if (preg_match('/^((http(s?):)?\/\/)/i', $url)) {
			return $url;
		}

		// Is theme folder located on the web (ie AWS)? Dont lookup parent themes...
		if (preg_match('/^((http(s?):)?\/\/)/i', $this->getAssetsPath())) {
			return $this->getAssetsPath($url);
		}

		// Check for valid {xxx} keys and replace them with the Theme's configuration value (in composer.json)
		preg_match_all('/\{(.*?)\}/', $url, $matches);
		foreach ($matches[1] as $param) {
			if (($value = $this->get("extra.theme.$param")) !== null) {
				$url = str_replace('{' . $param . '}', $value, $url);
			}
		}

		// Lookup asset in current's theme assets path
		$fullUrl = rtrim((empty($this->getAssetsPath()) ? '' : DIRECTORY_SEPARATOR) . $this->getAssetsPath($url), DIRECTORY_SEPARATOR);
		if (File::exists(public_path($fullUrl))) {
			$fullUrl = ltrim(str_replace('\\', '/', $fullUrl), '/');
			return $absolutePath ? asset('') . $fullUrl : $fullUrl;
		}

		// If not found then lookup in parent's theme assets path
		if ($parentTheme = $this->getParent()) {
			return $parentTheme->url($url, $absolutePath);
		} else { // No parent theme? Lookup in the public folder.
			if (File::exists(public_path($url))) {
				$url = ltrim(str_replace('\\', '/', $url), '/');
				return $absolutePath ? asset('') . $url : $url;
			}
		}

		\Log::warning("Asset [{$url}] not found for Theme [{$this->getName()}]");

		return ltrim(str_replace('\\', '/', $url));
	}

	/**
	 * List theme's available layouts
	 *
	 * @return Collection
	 */
	public function listLayouts()
	{
		$layouts = collect();

		$layoutDirs = $this->getViewPaths('layouts');
		foreach ($layoutDirs as $layoutDir) {
			foreach (glob($layoutDir . '/{**/*,*}.php', GLOB_BRACE) as $layout) {
				$layouts.put($layout, basename($layout, '.blade.php'));
			}
		}

		return $layouts;
	}

	/**
	 * Register theme's views in ViewFinder
	 *
	 * @return void
	 */
	protected function registerViews()
	{
		// Create symlink for public resources if not existing yet
		$assetsPath = $this->getPath('public');
		$publicAssetsPath = public_path($this->getAssetsPath());
		if (!File::exists($publicAssetsPath) && File::exists($assetsPath)) {
			app(Filesystem::class)->link($assetsPath, rtrim($publicAssetsPath, DIRECTORY_SEPARATOR));
		}

		// Register theme views path
		$paths = $this->getViewPaths();
		$paths = array_map(function ($path) {
			$path = rtrim($path, DIRECTORY_SEPARATOR);
			View::getFinder()->prependLocation("{$path}");

			return $path;
		}, $paths);

		// Update config view.paths to work with errors views
		if (is_array(Config::get('view.paths'))) {
			Config::set('view.paths', array_merge(Config::get('view.paths'), $paths));
		} else {
			Config::set('view.paths', $paths);
		}

		// Register all vendor views
		$vendorViewsPath = $this->getPath('resources/views/vendor');
		if (File::exists($vendorViewsPath)) {
			$directories = scandir($vendorViewsPath);
			foreach ($directories as $namespace) {
				if ($namespace != '.' && $namespace != '..') {
					$path = "{$vendorViewsPath}{$namespace}";

					if (!empty(Config::get('view.paths')) && is_array(Config::get('view.paths'))) {
						foreach (Config::get('view.paths') as $viewPath) {
							if (is_dir($appPath = $viewPath . '/vendor/' . $namespace)) {
								View::prependNamespace($namespace, $appPath);
							}
						}
					}
			
					View::prependNamespace($namespace, $path);
				}
			}
		}
	}

	/**
	 * Clean Path by replacing all / by DIRECTORY_SEPARATOR
	 *
	 * @param string $path
	 * 
	 * @return string
	 */
	protected function cleanPath($path = '')
	{
		$path = str_replace('/', DIRECTORY_SEPARATOR, $path);

		if ($path && !is_file($path) && !Str::endsWith($path, DIRECTORY_SEPARATOR)) {
			$path = $path . DIRECTORY_SEPARATOR;
		}

		return $path;
	}
}
