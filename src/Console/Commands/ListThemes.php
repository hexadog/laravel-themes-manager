<?php

namespace Hexadog\ThemesManager\Console\Commands;

use Illuminate\Console\Command;

class ListThemes extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all registered themes';

    /**
     * The table headers for the command.
     *
     * @var array
     */
    protected $headers = ['Name', 'Vendor', 'Version', 'Description', 'Extends', 'Default', 'Active'];

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Prompt for module's alias name.
     */
    public function handle()
    {
        $this->themes = [];

        $themes = \Theme::all();

        foreach ($themes as $theme) {
            $this->themes[] = [
                'name' => $theme->getName(),
                'vendor' => $theme->getVendor(),
                'version' => $theme->get('version'),
                'description' => $theme->get('description'),
                'extends' => $theme->getParent() ? $theme->getParent() : '',
                'default' => $theme->getName() === config('themes-manager.fallback_theme') ? 'X' : '',
                'active' => $theme->isActive() ? 'Yes' : 'No',
            ];
        }

        if (0 == count($this->themes)) {
            return $this->error("Your application doesn't have any theme.");
        }

        $this->table($this->headers, $this->themes);
    }
}
