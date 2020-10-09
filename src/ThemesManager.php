<?php

namespace Hexadog\ThemesManager;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Facades\Config;
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
	 * 
	 * @var \Illuminate\View\Factory
	 */
	private $view;

	/**
	 * File System
	 * 
	 * @var \Illuminate\Contracts\Filesystem\Filesystem
	 */
	private $files;

	/**
	 * The constructor.
	 *
	 * @param Factory $view
	 * @param Filesystem $files
	 * @param Translator $lang
	 */
	public function __construct(Factory $view, Filesystem $files, Translator $lang)
	{
		$this->view = $view;
		$this->files = $files;
		$this->lang = $lang;
		$this->basePath = Config::get('themes-manager.directory', 'themes');

		// Scan available themes
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
	 * Get all themes
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
	 * Get theme by name (or return all themes if no name given)
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
	 * Get current theme.
	 *
	 * @return Theme|null
	 */
	public function current(): ?Theme
	{
		return $this->themes
			->filter(function ($theme) {
				return $theme->enabled();
			})->first();
	}

	/**
	 * Enable a Theme from its name
	 * 
	 * @param string $name
	 * @param bool $withEvent
	 *
	 * @return ThemesManager
	 */
	public function enable(string $name, bool $withEvent = true): ThemesManager
	{
		if ($theme = $this->get($name)) {
			$theme->enable($withEvent);

			// Add Theme language files
			$this->lang->addNamespace('theme', $theme->getPath('lang'));
		}

		return $this;
	}

	/**
	 * Disable a Theme from its name
	 * 
	 * @param string $name
	 * @param bool $withEvent
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
	 * Get current theme's asset url
	 *
	 * @param string $asset
	 * @param boolean $absolutePath
	 *
	 * @return string
	 */
	public function asset(string $asset, $absolutePath = true): string
	{
		return $this->url($asset, $absolutePath);
	}

	/**
	 * Get current theme's style HTML tag for given asset
	 *
	 * @param string $asset
	 * @param boolean $absolutePath
	 * 
	 * @return string
	 */
	public function style(string $asset, $absolutePath = true): string
	{
		return sprintf(
			'<link media="all" type="text/css" rel="stylesheet" href="%s">',
			$this->url($asset, $absolutePath)
		);
	}

	/**
	 * Get current theme's script HTML tag for given asset
	 *
	 * @param  string $asset
	 * @param  string $mode ''|defer|async
	 * @param boolean $absolutePath
	 * @param  string $type
	 * @param  string $level
	 * 
	 * @return string
	 */
	public function script(string $asset, string $mode = '', $absolutePath = true, string $type = 'text/javascript', string $level = 'functionality'): string
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
	 * Get current theme's image HTML tag for given asset
	 *
	 * @param  string $asset
	 * @param  string $alt
	 * @param  string $class
	 * @param  array  $attributes
	 * @param boolean $absolutePath
	 * 
	 * @return string
	 */
	public function image(string $asset, string $alt = '', string $class = '', array $attributes = [], $absolutePath = true): string
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
	 * @return string
	 */
	public function mix($asset, $manifestDirectory = '')
	{
		return mix($this->url($asset), $manifestDirectory);
	}

	/**
	 * Get theme's asset url
	 *
	 * @param string $asset
	 * @param boolean $absolutePath
	 * 
	 * @return string|null
	 */
	public function url(string $asset, $absolutePath = true): ?string
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
	 * 
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
	 * Filter non active themes
	 * 
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
