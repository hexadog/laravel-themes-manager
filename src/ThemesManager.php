<?php

namespace Hexadog\ThemesManager;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;
use Hexadog\ThemesManager\Traits\ComposerTrait;
use Illuminate\Contracts\Translation\Translator;
use Hexadog\ThemesManager\Exceptions\ThemeNotFoundException;
use Hexadog\ThemesManager\Exceptions\ComposerLoaderException;

class ThemesManager
{
	use ComposerTrait;

	/**
	 * Theme Root Path.
	 *
	 * @var string
	 */
	protected $basePath;

	/**
	 * Scanned themes
	 * @var Collection
	 */
	private $themes;

	/**
	 * Translator.
	 *
	 * @var \Illuminate\Contracts\Translation\Translator
	 */
	protected $lang;

	/**
	 * View finder
	 * @var \Illuminate\View\Factory
	 */
	private $view;

	/**
	 * @var \Illuminate\Contracts\Filesystem\Filesystem
	 */
	private $files;

	/**
	 * Engine compiler.
	 *
	 * @var array
	 */
	protected $compilers = array();

	public function __construct(Factory $view, Filesystem $files, Translator $lang)
	{
		$this->view = $view;
		$this->files = $files;
		$this->lang = $lang;
		$this->basePath = config('themes-manager.directory', 'themes');

		// Scan available themes per group
		try {
			$this->themes = $this->scan($this->basePath, Theme::class);

			$this->themes->each(function ($theme) {
				$extendedThemeName = $theme->get('extra.theme.parent');
				if ($extendedThemeName) {
					if ($this->has($extendedThemeName)) {
						$extendedTheme = $this->get($extendedThemeName);
					} else {
						$extendedTheme = new Theme($theme->getPath());
					}
					$theme->setParent($extendedTheme);
				}
			});
		} catch (ComposerLoaderException $e) {
			return $this;
		}
	}

	/**
	 * Get all themes for group
	 *
	 * @return mixed
	 */
	public function all()
	{
		return $this->get();
	}

	/**
	 * Check if theme with given name exists
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function has(string $name = null)
	{
		return !is_null($this->themes->first(function ($theme) use ($name) {
			return $theme->getLowerName() === Str::lower($name);
		}));
	}

	/**
	 * Get theme by name and group (or return all themes if no parameter)
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get(string $name = null)
	{
		if (is_null($name)) {
			return $this->themes;
		} else {
			return $this->themes->first(function ($theme) use ($name) {
				return $theme->getLowerName() === Str::lower($name);
			});
		}
	}

	/**
	 * Set current active theme
	 *
	 * @param string $name Theme namespace
	 *
	 * @throws ThemeNotFoundException
	 *
	 * @return ThemesManager
	 */
	public function set(string $name): ThemesManager
	{
		if (!$this->has($name)) {
			throw new ThemeNotFoundException($name);
		}

		optional($this->current())->disable();

		$this->enable($name);

		return $this;
	}

	/**
	 * Get current theme for a group (current group if none provided).
	 *
	 * @param string $group
	 *
	 * @return Theme|null
	 */
	public function current()
	{
		return $this->themes
			->filter(function ($theme) {
				return $theme->enabled();
			})->first();
	}

	/**
	 * @param $alias
	 * @param $group
	 * @param bool|true $withEvent
	 *
	 * @return $this
	 */
	public function enable(string $name, bool $withEvent = true): ThemesManager
	{
		if ($theme = $this->get($name)) {
			$theme->enable($withEvent);

			// Create symlink for public resources if not existing yet
			$assetsPath = $theme->getPath('public');
			$publicAssetsPath = public_path($theme->getAssetsPath());
			if (!File::exists($publicAssetsPath) && File::exists($assetsPath)) {
				app(Filesystem::class)->link($assetsPath, rtrim($publicAssetsPath, DIRECTORY_SEPARATOR));
			}

			// Register theme views path
			$paths = $theme->getViewPaths();
			array_map(function ($path) {
				View::getFinder()->prependLocation("{$path}");
				View::addNamespace('theme', "{$path}");
			}, $paths);

			// TODO: set list of override hints into composer.json
			// Add theme views path to UI-Kit package
			$existingHints = app('view')->getFinder()->getHints();
			if (!empty($existingHints['package.ui-kit'])) {
				View::replaceNamespace('package.ui-kit', array_merge($paths, $existingHints['package.ui-kit']));
			}

			// Add Theme language files
			$this->lang->addNamespace('theme', $theme->getPath('lang'));
		}

		return $this;
	}

	/**
	 * @param string $name
	 * @param $group
	 *
	 * @return ThemesManager
	 */
	public function disable(string $name, bool $withEvent = true): ThemesManager
	{
		if ($theme = $this->get($name)) {
			$theme->disable($withEvent);
		}

		return $this;
	}

	/**
	 * @param type $asset
	 *
	 * @return string
	 */
	public function asset(string $asset, $absolutePath = false): string
	{
		return $this->url($asset, $absolutePath);
	}

	/**
	 * Return css link for $href
	 *
	 * @param  string $href
	 * @return string
	 */
	public function style(string $asset, $absolutePath = false): string
	{
		return sprintf(
			'<link media="all" type="text/css" rel="stylesheet" href="%s">',
			$this->url($asset, $absolutePath)
		);
	}

	/**
	 * Return script link for $href
	 *
	 * @param  string $href
	 * @param  string $mode ''|defer|async
	 * @param  string $type
	 * @param  string $level
	 * @return string
	 */
	public function script(string $asset, string $mode = '', $absolutePath = false, string $type = 'text/javascript', string $level = 'functionality'): string
	{
		return sprintf(
			'<script %s src="%s" data-type="%s" data-level="%s"></script>',
			$mode,
			$this->url($asset, $absolutePath),
			$type,
			$level
		);
	}

	/**
	 * Return img tag
	 *
	 * @param  string $src
	 * @param  string $alt
	 * @param  string $Class
	 * @param  array  $attributes
	 * @return string
	 */
	public function image(string $asset, string $alt = '', string $class = '', array $attributes = [], $absolutePath = false): string
	{
		return sprintf(
			'<img src="%s" alt="%s" class="%s" %s>',
			$this->url($asset, $absolutePath),
			$alt,
			$class,
			$this->htmlAttributes($attributes)
		);
	}

	/**
	 * Get the current theme path to a versioned Mix file.
	 *
	 * @param string $path
	 * @param string $manifestDirectory
	 *
	 * @return \Illuminate\Support\HtmlString|string
	 */
	public function mix($asset, $manifestDirectory = '')
	{
		return mix($this->url($asset), $manifestDirectory);
	}

	// Return url of current theme
	public function url(string $asset, $absolutePath = false): ?string
	{
		// Split asset name to find concerned theme name
		$assetParts = explode('::', $asset);
		if (count($assetParts) == 2) {
			$name = $assetParts[0];
			$asset = $assetParts[1];
		}

		// If no Theme set, return /$asset
		if (empty($name) && !$this->current()) {
			return '/' . ltrim($asset, '/');
		}

		if (!empty($name)) {
			return optional($this->get($name))->url($asset, $absolutePath);
		} else {
			return optional($this->current())->url($asset, $absolutePath);
		}
	}

	/**
	 * Return attributes in html format
	 *
	 * @param  array $attributes
	 * @return string
	 */
	private function htmlAttributes($attributes)
	{
		return join(' ', array_map(function ($key) use ($attributes) {
			if (is_bool($attributes[$key])) {
				return $attributes[$key] ? $key : '';
			}
			return $key . '="' . $attributes[$key] . '"';
		}, array_keys($attributes)));
	}

	/**
	 * @param Collection $themes
	 *
	 * @return Collection
	 */
	private function filterNonActiveThemes(Collection $themes)
	{
		return $themes
			->filter(function ($theme) {
				return $theme->enabled();
			});
	}
}
