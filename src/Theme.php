<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager;

use Hexadog\ThemesManager\Events\ThemeDisabled;
use Hexadog\ThemesManager\Events\ThemeDisabling;
use Hexadog\ThemesManager\Events\ThemeEnabled;
use Hexadog\ThemesManager\Events\ThemeEnabling;
use Hexadog\ThemesManager\Facades\ThemesManager;
use Hexadog\ThemesManager\Traits\HasTranslations;
use Hexadog\ThemesManager\Traits\HasViews;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

final class Theme
{
    use HasTranslations;
    use HasViews;

    /**
     * The theme name.
     */
    protected string $name;

    /**
     * The theme vendor.
     */
    protected string $vendor;

    /**
     * The theme version.
     */
    protected string $version = '0.1';

    /**
     * The theme description.
     */
    protected string $description = '';

    /**
     * The theme path.
     */
    protected string $path;

    /**
     * The Parent theme.
     */
    protected string|Theme|null $parent = null;

    /**
     * The theme screenshot.
     */
    protected string $screenshot = '';

    /**
     * The theme statud (enabled or not).
     */
    protected bool $enabled = false;

    /**
     * Theme extra data.
     */
    protected array $extra = [];

    /**
     * The constructor.
     */
    public function __construct(string $name, string $path)
    {
        $this->setName($name);
        $this->setPath($path);

        View::prependNamespace('theme.' . Str::snake($this->name), $this->getPath('resources/views'));

        // // Add theme.THEME_NAME namespace to be able to force views from specific theme
        // View::replaceNamespace('theme', $this->getPath('resources/views'));
    }

    /**
     * Create a new Theme.
     */
    public static function make(...$arguments): self
    {
        return new self(...$arguments);
    }

    /**
     * Get path.
     */
    public function getPath(?string $path = null): string
    {
        return $this->path . $path;
    }

    /**
     * Get assets path.
     */
    public function getAssetsPath(?string $path = null): string
    {
        return Config::get('themes-manager.symlink_path', 'themes') . '/' . mb_strtolower($this->vendor) . '/' . mb_strtolower($this->name) . ($path ? '/' . $path : '');
    }

    /**
     * Set extra data.
     */
    public function setExtra(array $extra): self
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * Get extra data.
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    /**
     * Set theme version.
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Set theme description.
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set theme name.
     */
    public function setName(string $name): self
    {
        // normalize theme name
        $name = str_replace(['-theme', 'theme-'], '', $name);

        $this->name = basename($name);
        $this->setVendor($name);

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set Theme path.
     */
    public function setPath(string $path): self
    {
        $this->path = Str::finish($path, '/');

        return $this;
    }

    /**
     * Set theme vendor.
     */
    public function setVendor(?string $vendor = null): self
    {
        if (Str::contains($vendor, '/')) {
            $this->vendor = dirname($vendor);
        } else {
            $this->vendor = $vendor;
        }

        return $this;
    }

    public function getVendor(): string
    {
        return $this->vendor;
    }

    /**
     * Check if has parent Theme.
     */
    public function hasParent(): bool
    {
        return ! is_null($this->parent);
    }

    /**
     * Set parent Theme.
     */
    public function setParent(string|Theme|null $theme): self
    {
        $this->parent = empty($theme) ? null : $theme;

        return $this;
    }

    /**
     * Get parent Theme.
     */
    public function getParent(): ?Theme
    {
        if (is_string($this->parent)) {
            $this->parent = ThemesManager::findByName($this->parent);
        }

        return $this->parent;
    }

    /**
     * Set theme screenshot.
     */
    public function setScreenshot(string $screenshot): self
    {

        $this->screenshot = $screenshot;

        return $this;
    }

    public function getScreenshotName(): ?string
    {
        return $this->screenshot;
    }

    public function getScreenshotImageUrl(): string
    {
        return $this->url($this->screenshot);
    }

    public function getScreenshotImageBase64(): ?string
    {
        $screenshotImage = $this->getAssetsPath($this->screenshot);

        if (! is_file($screenshotImage)) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode(File::get($screenshotImage));
    }

    /**
     * Determine whether the current theme activated.
     */
    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     *  Determine whether the current theme not disabled.
     */
    public function disabled(): bool
    {
        return ! $this->enabled();
    }

    /**
     * Disable the current theme.
     */
    public function disable(bool $withEvent = true): Theme
    {
        // Check if current is active and currently enabled
        if ($this->enabled()) {
            if ($withEvent) {
                event(new ThemeDisabling($this));
            }

            $this->enabled = false;

            if ($withEvent) {
                event(new ThemeDisabled($this));
            }
        }

        return $this;
    }

    /**
     * Enable the current theme.
     */
    public function enable(bool $withEvent = true): Theme
    {
        // Check if current is active and currently disabled
        if ($this->disabled()) {
            if ($withEvent) {
                event(new ThemeEnabling($this));
            }

            $this->enabled = true;
            $this->loadViews();
            $this->loadTranlastions();

            if ($withEvent) {
                event(new ThemeEnabled($this));
            }
        }

        return $this;
    }

    /**
     * Get theme asset url.
     */
    public function url(string $url, bool $absolute = true): string
    {
        $url = trim($url, '/');

        // return external URLs unmodified
        if (URL::isValidUrl($url)) {
            return $url;
        }

        // Is theme folder located on the web (ie AWS)? Dont lookup parent themes...
        if (URL::isValidUrl($this->getAssetsPath())) {
            return $this->getAssetsPath($url);
        }

        // Check for valid {xxx} keys and replace them with the Theme's configuration value (in composer.json)
        if (preg_match_all('/(\{.*?\})/', $url, $matches)) {
            $url = str_replace($matches, $this->extra, $url);
        }

        // Check into Vite manifest file
        $manifesPath = $this->getAssetsPath('manifest.json');
        if (file_exists($manifesPath)) {
            $manifest = file_get_contents($manifesPath);
            $manifest = json_decode($manifest, true);

            if (array_key_exists($url, $manifest)) {
                // Lookup asset in current's theme assets path
                $fullUrl = $this->getAssetsPath($manifest[$url]['file']);

                return $absolute ? asset($fullUrl) : $fullUrl;
            }
        }

        // Lookup asset in current's theme assets path
        $fullUrl = $this->getAssetsPath($url);

        if (file_exists(public_path($fullUrl))) {
            return $absolute ? asset($fullUrl) : $fullUrl;
        }

        // If not found then lookup in parent's theme assets path
        if ($parentTheme = $this->getParent()) {
            return $parentTheme->url($url, $absolute);
        }

        // No parent theme? Lookup in the public folder.
        if (file_exists(public_path($url))) {
            return $absolute ? asset('') . $url : $url;
        }

        Log::warning("Asset [{$url}] not found for Theme [{$this->name}]");

        return ltrim(str_replace('\\', '/', $url));
    }

    /**
     * Create public assets directory path.
     */
    protected function assertPublicAssetsPath(): void
    {
        $themeAssetsPath = $this->getPath('public');

        if (file_exists($themeAssetsPath)) {
            $publicThemeAssetsPath = public_path($this->getAssetsPath());
            $publicThemeVendorPath = dirname($publicThemeAssetsPath);

            // Create target public theme vendor directory if required
            if (! file_exists($publicThemeVendorPath)) {
                app(Filesystem::class)->makeDirectory($publicThemeVendorPath, 0755, true);
            }

            // Create target symlink public theme assets directory if required
            if (! file_exists($publicThemeAssetsPath)) {
                if (Config::get('themes-manager.symlink_relative', false)) {
                    app(Filesystem::class)->relativeLink($themeAssetsPath, rtrim($publicThemeAssetsPath, '/'));
                } else {
                    app(Filesystem::class)->link($themeAssetsPath, rtrim($publicThemeAssetsPath, '/'));
                }
            }
        }
    }
}
