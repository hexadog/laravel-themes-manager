<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Class BladeServiceProvider.
 */
class BladeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     */
    protected bool $defer = false;

    /**
     * Register any misc. blade extensions.
     */
    public function boot(): void
    {
        Blade::directive('pagetitle', function ($expression) {
            $expression = self::parseMultipleArgs($expression);
            $with_app_name = is_string($expression->get(0)) && strlen($expression->get(0)) ? filter_var($expression->get(0), FILTER_VALIDATE_BOOLEAN) : true;
            $separator = $expression->get(1) ?? '-';

            return "<?php echo '<title>' . page_title({$with_app_name}, '{$separator}') . '</title>'; ?>";
        });

        Blade::directive('themeAsset', function ($expression) {
            $expression = self::parseMultipleArgs($expression);

            return "<?php theme_asset(...{$expression}); ?>";
        });

        Blade::directive('themeImage', function ($expression) {
            $expression = self::parseMultipleArgs($expression);

            return "<?php theme_image(...{$expression}); ?>";
        });

        Blade::directive('themeScript', function ($expression) {
            $expression = self::parseMultipleArgs($expression);

            return "<?php theme_script(...{$expression}); ?>";
        });

        Blade::directive('themeStyle', function ($expression) {
            $expression = self::parseMultipleArgs($expression);
            [$asset, $absolutePath] = $expression;

            return "<?php theme_style({$asset}, {$absolutePath}); ?>";
        });
    }

    /**
     * Parse expression.
     */
    public static function parseMultipleArgs(string $expression): \Illuminate\Support\Collection
    {
        return collect(explode(',', $expression))->map(function ($item) {
            return trim($item);
        });
    }
}
