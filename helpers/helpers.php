<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;

if (! function_exists('page_title')) {
    /**
     * Get formatted page title
     */
    function page_title(string $title, bool $withAppName = true, string $separator = '-', bool $invert = false): string
    {
        if (View::hasSection('title')) {
            $title = View::getSection('title');
        }

        if (strlen($title) && $withAppName) {
            if ($invert) {
                return $title . ' ' . trim(e($separator)) . ' ' . config('app.name');
            }

            return config('app.name') . ' ' . trim(e($separator)) . ' ' . $title;
        }

        return config('app.name');
    }
}

if (! function_exists('theme')) {
    /**
     * Set theme.
     */
    function theme(?string $themeName = null): Hexadog\ThemesManager\Theme
    {
        if ($themeName) {
            \Theme::set($themeName);
        }

        return \Theme::current();
    }
}

if (! function_exists('theme_asset')) {
    /**
     * Generate an url for the theme asset.
     */
    function theme_asset(string $asset, bool $absolutePath = true, bool $version = true): string
    {
        return \Theme::url($asset, $absolutePath, $version);
    }
}

if (! function_exists('theme_style')) {
    /**
     * Generate a secure asset path for the theme asset.
     */
    function theme_style(string $asset, bool $absolutePath = true, bool $version = true): string
    {
        return \Theme::style($asset, $absolutePath, $version);
    }
}

if (! function_exists('theme_script')) {
    /**
     * Generate a secure asset path for the theme asset.
     */
    function theme_script(string $asset, string $mode = '', bool $absolutePath = true, string $type = 'text/javascript', string $level = 'functionality', bool $version = true): string
    {
        return \Theme::script($asset, $mode, $absolutePath, $type, $level, $version);
    }
}

if (! function_exists('theme_image')) {
    /**
     * Generate a secure asset path for the theme asset.
     */
    function theme_image(string $asset, string $alt = '', string $class = '', array $attributes = [], bool $absolutePath = true, bool $version = true): string
    {
        return \Theme::image($asset, $alt, $class, $attributes, $absolutePath, $version);
    }
}
