<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Traits;

use Illuminate\Translation\Translator;

trait HasTranslations
{
    /**
     * Get theme translations paths.
     */
    public function getTranslationPaths(string $path = ''): array
    {
        // Build Paths array.
        // All paths are relative to Config::get('themes-manager.directory')
        $paths = [];
        $theme = $this;

        do {
            $translationsPath = $theme->getPath('lang' . ($path ? "/{$path}" : ''));

            if (! file_exists($translationsPath)) {
                $translationsPath = $theme->getPath('resources/lang' . ($path ? "/{$path}" : ''));
            }

            if (file_exists($translationsPath) && ! in_array($translationsPath, $paths)) {
                $paths[] = $translationsPath;
            }
        } while ($theme = $theme->getParent());

        return array_reverse($paths);
    }

    /**
     * Register theme's translations.
     */
    protected function loadTranlastions(): void
    {
        // Register Translation paths
        $paths = $this->getTranslationPaths();
        $translator = app()->make(Translator::class);

        foreach ($paths as $path) {
            $translator->addNamespace('theme', $path);
        }
    }
}
