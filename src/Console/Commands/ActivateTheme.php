<?php

namespace Hexadog\ThemesManager\Console\Commands;

use Hexadog\ThemesManager\Console\Commands\AbstractCommand;

class ActivateTheme extends AbstractCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'theme:activate {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate a theme';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Prompt for module's alias name
     *
     */
    public function handle()
    {
        $this->validateName();

        if ($this->theme->isActive()) {
            $this->error("Theme with name {$this->argument('name')} is already active!");

            return false;
        }

        $this->sectionMessage('Themes Manager', 'Activating theme...');
        if ($this->theme->activate()) {
            $this->sectionMessage('Themes Manager', 'Theme activated succefully');
        } else {
            $this->error("Error while activating Theme with name {$this->argument('name')}!");
        }
    }
}
