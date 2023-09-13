<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Traits;

use Illuminate\Mail\Markdown;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\View;

trait HasViews
{
    /**
     * Get theme views paths.
     * Build Paths array.
     * All paths are relative to Config::get('themes-manager.directory').
     */
    public function getViewPaths(string $path = ''): array
    {
        $paths = [];
        $theme = $this;

        do {
            $viewsPath = $theme->getPath('resources/views' . ($path ? "/{$path}" : ''));

            if (! in_array($viewsPath, $paths)) {
                $paths[] = $viewsPath;
            }

            $theme = $theme->getParent();
        } while ($theme);

        return $paths;
    }

    /**
     * List theme's available layouts.
     */
    public function listLayouts(): \Illuminate\Support\Collection
    {
        $layouts = collect();

        $layoutDirs = $this->getViewPaths('layouts');

        foreach ($layoutDirs as $layoutDir) {
            $layoutFiles = glob($layoutDir . '/{**/*,*}.php', GLOB_BRACE);

            if ($layoutFiles) {
                foreach ($layoutFiles as $layout) {
                    $layouts->put($layout, basename($layout, '.blade.php'));
                }
            }
        }

        return $layouts;
    }

    /**
     * Register theme's views in ViewFinder.
     */
    protected function loadViews(): void
    {
        $this->assertPublicAssetsPath();

        // Register theme views path
        $paths = array_reverse($this->getViewPaths());

        foreach ($paths as $path) {
            View::getFinder()->prependLocation("{$path}");
        }

        // Update config view.paths to work with errors views
        Config::set('view.paths', array_merge($paths, Arr::wrap(Config::get('view.paths'))));

        $this->loadVendorViews();
        $this->loadMailComponentPaths();
    }

    /**
     * Register theme's vendor views.
     */
    protected function loadVendorViews(): void
    {
        $vendorViewsPath = $this->getPath('resources/views/vendor');

        if (file_exists($vendorViewsPath)) {
            $directories = glob($vendorViewsPath . '/*', GLOB_ONLYDIR);

            if ($directories) {
                foreach ($directories as $path) {
                    View::prependNamespace(basename($path), $path);
                }
            }
        }
    }

    /**
     * Register Mail Markdown components.
     */
    protected function loadMailComponentPaths(): void
    {
        $mailComponentPaths = $this->getPath('resources/views/vendor/mail');

        app()->make(Markdown::class)->loadComponentsFrom(Arr::prepend(Arr::wrap(Config::get('mail.markdown.paths')), $mailComponentPaths));
    }
}
