<?php

namespace Hexadog\ThemesManager;

use Illuminate\Support\Str;
use Illuminate\Container\Container;
use Hexadog\ThemesManager\Events\ThemeEnabled;
use Hexadog\ThemesManager\Events\ThemeDisabled;
use Hexadog\ThemesManager\Events\ThemeEnabling;
use Hexadog\ThemesManager\Traits\ComposerTrait;
use Hexadog\ThemesManager\Events\ThemeDisabling;

class Theme
{
	use ComposerTrait;

	protected $type = null;

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
		return config('themes-manager.symlink_path', 'themes') . DIRECTORY_SEPARATOR . mb_strtolower($this->getName()) . DIRECTORY_SEPARATOR . $this->cleanPath($path);
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

	public function hasParent(): bool
	{
		return !is_null($this->parent);
	}

	public function setParent(Theme $theme)
	{
		$this->parent = $theme;
	}

	/**
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
	 * Disable the current theme.
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
	 * Enable the current theme.
	 */
	public function enable(bool $withEvent = true): Theme
	{
		if ($withEvent) {
			event(new ThemeEnabling($this->getName()));
		}

		$this->setStatus(true);

		if ($withEvent) {
			event(new ThemeEnabled($this->getName()));
		}

		return $this;
	}

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
		if (file_exists(public_path($fullUrl))) {
			return $absolutePath ? config('app.url') . $fullUrl : $fullUrl;
		}

		// If not found then lookup in parent's theme assets path
		if ($parentTheme = $this->getParent()) {
			return $parentTheme->url($url, $absolutePath);
		} else { // No parent theme? Lookup in the public folder.
			if (file_exists(public_path($url))) {
				return $absolutePath ? config('app.url') . $url : DIRECTORY_SEPARATOR . $url;
			}
		}

		\Log::warning("Asset not found [{$url}] in Theme [{$this->getName()}]");

		return DIRECTORY_SEPARATOR . $url;
	}

	public function listLayouts()
	{
		$layouts = [];

		$layoutDirs = $this->getViewPaths('layouts');
		foreach ($layoutDirs as $layoutDir) {
			foreach (glob($layoutDir . '/{**/*,*}.php', GLOB_BRACE) as $layout) {
				$layouts[] = basename($layout, '.blade.php');
			}
		}

		return $layouts;
	}

	/**
	 * Clean Path by replacing all / by DIRECTORY_SEPARATOR
	 *
	 * @param string $path
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
