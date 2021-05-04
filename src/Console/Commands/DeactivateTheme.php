<?php

namespace Hexadog\ThemesManager\Console\Commands;

class DeactivateTheme extends AbstractCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'theme:deactivate {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate a theme';

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
        $this->validateName();

        if (!$this->theme->isActive()) {
            $this->error("Theme with name {$this->argument('name')} is already deactivated!");

            return false;
        }

        $this->sectionMessage('Themes Manager', 'Deactivating theme...');

        if ($this->theme->deactivate()) {
            $this->sectionMessage('Themes Manager', 'Theme deactivated succefully');
        } else {
            $this->error("Error while deactivating Theme with name {$this->argument('name')}!");
        }
    }
}
