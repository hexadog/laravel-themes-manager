<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Console\Commands;

use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\Console\Command;

class ListThemes extends Command
{
    /**
     * The console command name.
     */
    protected $name = 'theme:list';

    /**
     * The console command description.
     */
    protected $description = 'List all registered themes';

    /**
     * The table headers for the command.
     */
    protected $headers = ['Name', 'Vendor', 'Version', 'Description', 'Screenshot', 'Extends', 'Default', 'Active'];

    /**
     * List of existing themes.
     */
    protected array $themes = [];

    /**
     * Prompt for module's alias name.
     */
    public function handle(): void
    {
        $themes = ThemesManager::all();

        foreach ($themes as $theme) {
            $this->themes[] = [
                'name'        => $theme->getName(),
                'vendor'      => $theme->getVendor(),
                'version'     => $theme->getVersion(),
                'description' => $theme->getDescription(),
                'screenshot'  => $theme->getScreenshotName(),
                'extends'     => $theme->getParent() ? $theme->getParent()->getName() : '',
                'default'     => $theme->getName() === config('themes-manager.fallback_theme') ? 'X' : '',
            ];
        }

        if (count($this->themes) === 0) {
            $this->error("Your application doesn't have any theme.");
        } else {
            $this->table($this->headers, $this->themes);
        }
    }
}
