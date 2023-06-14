<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager;

use Hexadog\ThemesManager\Exceptions\ThemeNotFoundException;
use Hexadog\ThemesManager\Traits\HasCache;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ThemesManager
{
    use HasCache;

    /**
     * Scanned themes.
     */
    private Collection $themes;

    /**
     * The constructor.
     */
    public function __construct()
    {
        if (Config::get('themes-manager.cache.enabled', false)) {
            $this->themes = $this->getCache();
        } else {
            $this->themes = ThemeFinder::find();
        }
    }

    /**
     * Get all themes.
     */
    public function all(): Collection
    {
        return $this->themes;
    }

    /**
     * Check if theme with given name exists.
     */
    public function has(?string $name = null): bool
    {
        return ! is_null($this->findByName($name, null));
    }

    /**
     * Get theme by name (or return all themes if no name given).
     */
    public function get(?string $name = null): ?Theme
    {
        return $this->findByName($name, null);
    }

    /**
     * Set current active theme.
     */
    public function set(string $name): ThemesManager
    {
        if (! $this->has($name)) {
            throw new ThemeNotFoundException($name);
        }

        optional($this->current())->disable();

        $this->enable($name);

        return $this;
    }

    /**
     * Get current theme.
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
     */
    public function enable(string $name, bool $withEvent = true): ThemesManager
    {
        if ($theme = $this->get($name)) {
            $theme->enable($withEvent);
        }

        return $this;
    }

    /**
     * Disable a Theme from its name.
     */
    public function disable(string $name, bool $withEvent = true): ThemesManager
    {
        if ($theme = $this->get($name)) {
            $theme->disable($withEvent);
        }

        return $this;
    }

    /**
     * Get current theme's asset url.
     */
    public function asset(string $asset, bool $absolute = true): string
    {
        return $this->url($asset, $absolute);
    }

    /**
     * Get current theme's style HTML tag for given asset.
     */
    public function style(string $asset, bool $absolute = true): string
    {
        return sprintf(
            '<link media="all" type="text/css" rel="stylesheet" href="%s">',
            $this->url($asset, $absolute)
        );
    }

    /**
     * Get current theme's script HTML tag for given asset.
     */
    public function script(string $asset, string $mode = '', bool $absolute = true, string $type = 'text/javascript', string $level = 'functionality'): string
    {
        return sprintf(
            '<script %s src="%s" data-type="%s" data-level="%s"></script>',
            $mode,
            $this->url($asset, $absolute),
            $type,
            $level
        );
    }

    /**
     * Get current theme's image HTML tag for given asset.
     */
    public function image(string $asset, string $alt = '', string $class = '', array $attributes = [], bool $absolute = true): string
    {
        return sprintf(
            '<img src="%s" alt="%s" class="%s" %s>',
            $this->url($asset, $absolute),
            $alt,
            $class,
            $this->htmlAttributes($attributes)
        );
    }

    /**
     * Get the current theme path to a versioned Mix file.
     */
    public function mix(string $asset, string $manifestDirectory = ''): string
    {
        return mix($this->url($asset), $manifestDirectory);
    }

    /**
     * Get theme's asset url.
     */
    public function url(string $asset, bool $absolute = true): ?string
    {
        // Split asset name to find concerned theme name
        if (Str::contains($asset, '::')) {
            $assetParts = explode('::', $asset);

            return optional($this->findByName($assetParts[0]))->url($assetParts[1], $absolute);
        }

        // If no Theme set, return /$asset
        if (! $this->current()) {
            return Str::start($asset, '/');
        }

        return $this->current()->url($asset, $absolute);
    }

    /**
     * Find a theme by given name and vendor (optional)
     * name can include vendor prefix (ie: hexadog/default)
     * If no vendor provided and name not prefixed by vendor
     * the first theme with given name is returned.
     */
    public function findByName(string $name, ?string $vendor = null): ?Theme
    {
        // normalize theme name
        $name = str_replace(['-theme', 'theme-'], '', $name);

        // Try to find vendor in name
        if (($pos = strpos($name, '/')) !== false) {
            $vendor = substr($name, 0, $pos);
            $name = substr($name, $pos + 1, strlen($name));
        }

        return $this->themes->first(function ($theme) use ($name, $vendor) {
            if (is_null($vendor)) {
                return Str::lower($theme->getName()) === Str::lower($name);
            }

            return Str::lower($theme->getName()) === Str::lower($name) && Str::lower($theme->getVendor()) === Str::lower($vendor);
        });
    }

    /**
     * Return attributes in html format.
     */
    private function htmlAttributes(array $attributes): string
    {
        return implode(' ', array_map(function ($key) use ($attributes) {
            if (is_bool($attributes[$key])) {
                return $attributes[$key] ? $key : '';
            }

            return $key . '="' . $attributes[$key] . '"';
        }, array_keys($attributes)));
    }
}
