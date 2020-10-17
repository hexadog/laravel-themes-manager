<?php

namespace Hexadog\ThemesManager\Http\Middleware;

use Closure;
use Hexadog\ThemesManager\Facades\ThemesManager;

class ThemeLoader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param String $theme
     * @param String $layout
     * @return mixed
     */
    public function handle($request, Closure $next, $theme = null)
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
