<?php

namespace Hexadog\ThemesManager\Http\Middleware;

use Closure;
use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\Http\Request;

class ThemeLoader
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param string                   $theme
     * @param string                   $layout
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $theme = null)
    {
        // Do not load theme if API request
        if ($request->expectsJson()) {
            return $next($request);
        }

        if (!empty($theme)) {
            ThemesManager::set($theme);
        } else {
            if ($theme = config('themes-manager.fallback_theme')) {
                ThemesManager::set($theme);
            }
        }

        return $next($request);
    }
}
