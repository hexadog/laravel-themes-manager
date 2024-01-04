<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Http\Middleware;

use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\Http\Request;

class ThemeLoader
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next, ?string $theme = null)
    {
        // Do not load theme if API request or App is running in console
        if ($request->expectsJson() || app()->runningInConsole()) {
            return $next($request);
        }

        if (! is_null($theme)) {
            ThemesManager::set($theme);
        } else {
            if ($theme = config('themes-manager.fallback_theme')) {
                ThemesManager::set($theme);
            }
        }

        return $next($request);
    }
}
