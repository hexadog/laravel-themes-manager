<?php

use Illuminate\Support\Facades\View;

if (!function_exists('page_title')) {
    /**
     * Get formatted page title
     *
     * @param  bool  $with_app_name
     * @param  string  $separator
     * @return string
     */
    function page_title(string $title, bool $withAppName = true, $separator = '-', $invert = false): string
    {
        if (View::hasSection('title')) {
            $title = View::getSection('title');
        }

        if (!empty($title) && $withAppName) {
            if ($invert) {
                return $title . " " . trim(e($separator)) . " " . config('app.name');
            } else {
                return config('app.name') . " " . trim(e($separator)) . " " . $title;
            }
        } else {
            return config('app.name');
        }
    }
}

if (!function_exists('theme')) {
    /**
     * Set theme.
     *
     * @param  string  $themeName
     * @return \Hexadog\ThemesManager\Theme
     */
    function theme($themeName = null)
    {
        if ($themeName) {
            \Theme::set($themeName);
        }
        
        return \Theme::current();
    }
}

if (!function_exists('theme_asset')) {
	/**
	 * Generate an url for the theme asset.
	 *
	 * @param  string  $asset
	 * @param  bool  $absolutePath
	 * @return string
	 */
	function theme_asset(string $asset, $absolutePath = true)
	{
		return \Theme::url($asset, $absolutePath);
	}
}

if (!function_exists('theme_style')) {
	/**
	 * Generate a secure asset path for the theme asset.
	 *
	 * @param  string  $asset
	 * @param  bool  $absolutePath
	 * @return string
	 */
	function theme_style(string $asset, $absolutePath = true)
	{
		return \Theme::style($asset, $absolutePath);
	}
}

if (!function_exists('theme_script')) {
	/**
	 * Generate a secure asset path for the theme asset.
	 *
	 * @param  string  $asset
	 * @param  string  $mode
	 * @param  bool  $absolutePath
	 * @param  string  $type
	 * @param  string  $level
	 * @return string
	 */
	function theme_script(string $asset, string $mode = '', $absolutePath = true, string $type = 'text/javascript', string $level = 'functionality')
	{
		return \Theme::script($asset, $mode, $absolutePath, $type, $level);
	}
}


if (!function_exists('theme_image')) {
	/**
	 * Generate a secure asset path for the theme asset.
	 *
	 * @param  string  $asset
	 * @return string
	 */
	function theme_image(string $asset, string $alt = '', string $class = '', array $attributes = [], $absolutePath = true)
	{
		return \Theme::image($asset, $alt, $class, $attributes, $absolutePath);
	}
}
