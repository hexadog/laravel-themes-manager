<?php

namespace Hexadog\ThemesManager;

use Hexadog\ThemesManager\Exceptions\ComposerLoaderException;
use Hexadog\ThemesManager\Exceptions\ThemeNotActiveException;
use Hexadog\ThemesManager\Exceptions\ThemeNotFoundException;
use Hexadog\ThemesManager\Traits\ComposerTrait;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ThemesManager
{
    use ComposerTrait;

    /**
     * Composaer package type.
     *
     * @var string
     */
    protected $packageType = 'laravel-theme';

    /**
     * Translator.
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $lang;

    /**
     * Laravel default view paths.
     *
     * @var [type]
     */
    protected $defaultViewPaths;

    /**
     * Laravel default Mail view paths.
     *
     * @var [type]
     */
    protected $defaultMailViewPaths;

    /**
     * Scanned themes.
     *
     * @var \Illuminate\Support\Collection
     */
    private $themes;

    /**
     * View finder.
     *
     * @var \Illuminate\View\Factory
     */
    private $view;

    /**
     * The constructor.
     *
     * @param \Illuminate\View\Factory       $view
     * @param \Illuminate\Cache\CacheManager $lang
     */
    public function __construct(Factory $view, Translator $lang)
    {
        $this->view = $view;
        $this->lang = $lang;

        // Save default Laravel View Paths
        $this->defaultViewPaths = Config::get('view.paths');
        // Save default Laravel Mail View Paths
        $this->defaultMailViewPaths = Config::get('mail.markdown.paths');

        if (Config::get('themes-manager.cache.enabled', false)) {
            $this->themes = $this->getCache();
        } else {
            $this->themes = $this->findThemes();
        }
    }

    /**
     * Get all themes.
     *
     * @return mixed
     */
    public function all()
    {
        return $this->get();
    }

    /**
     * Build cache of available themes.
     */
    public function buildCache(): bool
    {
        return Cache::put(Config::get('themes-manager.cache.key', 'themes-manager'), $this->findThemes(), Config::get('themes-manager.cache.lifetime', 86400));
    }

    /**
     * Clear the themes cache if it is enabled.
     */
    public function clearCache(): bool
    {
        if (true === Config::get('themes-manager.cache.enabled', false)) {
            return Cache::forget(Config::get('themes-manager.cache.key', 'themes-manager'));
        }

        return true;
    }

    /**
     * Check if theme with given name exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name = null, ?Collection $themes = null)
    {
        return !is_null($this->findByName($name, null, $themes));
    }

    /**
     * Get theme by name (or return all themes if no name given).
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name = null, ?Collection $themes = null)
    {
        if (is_null($name)) {
            return $this->themes;
        }

        return $this->findByName($name, null, $themes);
    }

    /**
     * Set current active theme.
     *
     * @param string $name Theme namespace
     *
     * @throws \Hexadog\ThemesManager\Exceptions\ThemeNotFoundException
     *
     * @return \Hexadog\ThemesManager\ThemesManager
     */
    public function set(string $name): ThemesManager
    {
        if (!$this->has($name)) {
            throw new ThemeNotFoundException($name);
        }

        if (!$this->get($name)->isActive()) {
            throw new ThemeNotActiveException($this->getName());
        }

        optional($this->current())->disable();

        $this->enable($name);

        return $this;
    }

    /**
     * Get current theme.
     *
     * @return null|\Hexadog\ThemesManager\Theme
     */
    public function current(): ?Theme
    {
        return $this->themes
            ->filter(function ($theme) {
                return $theme->enabled();
            })->first();
    }

    /**
     * Enable a Theme from its name.
     *
     * @return \Hexadog\ThemesManager\ThemesManager
     */
    public function enable(string $name, bool $withEvent = true): ThemesManager
    {
        if ($theme = $this->get($name)) {
            if (!$theme->isActive()) {
                throw new ThemeNotActiveException($name);
            }

            $theme->enable($withEvent, $this->defaultViewPaths, $this->defaultMailViewPaths);

            // Add Theme language files
            $this->lang->addNamespace('theme', $theme->getPath('lang'));
        }

        return $this;
    }

    /**
     * Disable a Theme from its name.
     *
     * @return \Hexadog\ThemesManager\ThemesManager
     */
    public function disable(string $name, bool $withEvent = true): ThemesManager
    {
        if ($theme = $this->get($name)) {
            if (!$theme->isActive()) {
                throw new ThemeNotActiveException($name);
            }

            $theme->disable($withEvent);
        }

        return $this;
    }

    /**
     * Get current theme's asset url.
     *
     * @param bool $absolutePath
     */
    public function asset(string $asset, $absolutePath = true, bool $version = true): string
    {
        return $this->url($asset, $absolutePath, $version);
    }

    /**
     * Get current theme's style HTML tag for given asset.
     *
     * @param bool $absolutePath
     */
    public function style(string $asset, $absolutePath = true, bool $version = true): string
    {
        return sprintf(
            '<link media="all" type="text/css" rel="stylesheet" href="%s">',
            $this->url($asset, $absolutePath, $version)
        );
    }

    /**
     * Get current theme's script HTML tag for given asset.
     *
     * @param string $mode         ''|defer|async
     * @param bool   $absolutePath
     */
    public function script(string $asset, string $mode = '', $absolutePath = true, string $type = 'text/javascript', string $level = 'functionality', bool $version = true): string
    {
        return sprintf(
            '<script %s src="%s" data-type="%s" data-level="%s"></script>',
            $mode,
            $this->url($asset, $absolutePath, $version),
            $type,
            $level
        );
    }

    /**
     * Get current theme's image HTML tag for given asset.
     *
     * @param bool $absolutePath
     */
    public function image(string $asset, string $alt = '', string $class = '', array $attributes = [], $absolutePath = true, bool $version = true): string
    {
        return sprintf(
            '<img src="%s" alt="%s" class="%s" %s>',
            $this->url($asset, $absolutePath, $version),
            $alt,
            $class,
            $this->htmlAttributes($attributes)
        );
    }

    /**
     * Get the current theme path to a versioned Mix file.
     *
     * @param string $manifestDirectory
     * @param mixed  $asset
     *
     * @return string
     */
    public function mix($asset, $manifestDirectory = '')
    {
        return mix($this->url($asset), $manifestDirectory);
    }

    /**
     * Get theme's asset url.
     *
     * @param bool $absolutePath
     */
    public function url(string $asset, $absolutePath = true, bool $version = true): ?string
    {
        // Split asset name to find concerned theme name
        $assetParts = explode('::', $asset);
        if (2 == count($assetParts)) {
            $name = $assetParts[0];
            $asset = $assetParts[1];
        }

        // If no Theme set, return /$asset
        if (empty($name) && !$this->current()) {
            return '/' . ltrim($asset, '/');
        }

        if (!empty($name)) {
            return optional($this->get($name))->url($asset, $absolutePath, $version);
        }

        return optional($this->current())->url($asset, $absolutePath, $version);
    }

    /**
     * Filter non active themes.
     */
    public function filterNonActive(): Collection
    {
        return $this->themes->filter(function ($theme) {
            return $theme->isActive();
        });
    }

    /**
     * Find a theme by given name and vendor (optional)
     * name can include vendor prefix (ie: hexadog/default)
     * If no vendor provided and name not prefixed by vendor
     * the first theme with given name is returned.
     *
     * @param string $name
     */
    protected function findByName(string $name = null, string $vendor = null, ?Collection $themes = null)
    {
        if (is_null($name)) {
            return null;
        }

        $themes = $themes ?? $this->themes;

        return $themes ? $themes->first(function ($theme) use ($name, $vendor) {
            // normalize module name
            $name = str_replace(['-theme', 'theme-'], '', $name);
            // Check if $name contains vendor
            if (($pos = strpos($name, '/')) !== false) {
                $vendor = substr($name, 0, $pos);
                $name = substr($name, $pos + 1, strlen($name));

                return Str::lower($theme->getName()) === Str::lower($name) && $theme->getLowerVendor() === Str::lower($vendor);
            }
            if (is_null($vendor)) {
                return $theme->getLowerName() === Str::lower($name);
            }

            return $theme->getLowerName() === Str::lower($name) && $theme->getLowerVendor() === Str::lower($vendor);
        }) : null;
    }

    /**
     * Find all available themes.
     */
    protected function findThemes(): Collection
    {
        $themes = collect();

        // Scan available themes
        try {
            $themes = $this->scan(Config::get('themes-manager.directory', 'themes'), Theme::class);

            $themes->each(function ($theme) use ($themes) {
                $extendedThemeName = $theme->get('extra.theme.parent');
                if ($extendedThemeName) {
                    if ($this->has($extendedThemeName, $themes)) {
                        $extendedTheme = $this->get($extendedThemeName, $themes);
                    } else {
                        $extendedTheme = new Theme($theme->getPath());
                    }

                    if ($extendedTheme) {
                        $theme->setParent($extendedTheme);
                    }
                }
            });
        } catch (ComposerLoaderException $e) {
        }

        return $themes;
    }

    /**
     * Get cached themes.
     */
    protected function getCache(): Collection
    {
        return Cache::remember(Config::get('themes-manager.cache.key', 'themes-manager'), Config::get('themes-manager.cache.lifetime', 86400), function () {
            return $this->findThemes();
        });
    }

    /**
     * Return attributes in html format.
     *
     * @param array $attributes
     */
    private function htmlAttributes($attributes): string
    {
        return join(' ', array_map(function ($key) use ($attributes) {
            if (is_bool($attributes[$key])) {
                return $attributes[$key] ? $key : '';
            }

            return $key . '="' . $attributes[$key] . '"';
        }, array_keys($attributes)));
    }
}
