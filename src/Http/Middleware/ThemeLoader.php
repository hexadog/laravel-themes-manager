<?php

namespace Hexadog\ThemesManager\Http\Middleware;

use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\Http\Request;

class ThemeLoader
{
    /**
     * Handle an incoming request.
     *
     * @param  string  $theme
     * @param  string  $layout
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, $theme = null)
    {
        // Do not load theme if API request or App is running in console
        if ($request->expectsJson() || app()->runningInConsole()) {
            return $next($request);
        }

        if (! empty($theme)) {
            ThemesManager::set($theme);
        } else {
            if ($theme = config('themes-manager.fallback_theme')) {
                ThemesManager::set($theme);
            }
        }

        return $next($request);
    }
}
