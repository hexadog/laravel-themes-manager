<?php

namespace Hexadog\ThemesManager\Console\Commands;

use Hexadog\ThemesManager\Console\Commands\Traits\BlockMessage;
use Hexadog\ThemesManager\Console\Commands\Traits\SectionMessage;
use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\Console\Command;

class ActivateTheme extends Command
{
    use BlockMessage;
    use SectionMessage;

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
        if (!ThemesManager::has($this->argument('name'))) {
            $this->error("Theme with name {$this->argument('name')} does not exists!");

            return false;
        }

        $theme = ThemesManager::get($this->argument('name'));

        if ($theme->isActive()) {
            $this->error("Theme with name {$this->argument('name')} is already active!");

            return false;
        }

        $this->sectionMessage('Themes Manager', 'Activating theme...');
        if ($theme->activate()) {
            $this->sectionMessage('Themes Manager', 'Theme deactivated succefully');
        } else {
            $this->error("Error while activating Theme with name {$this->argument('name')}!");
        }
        $this->sectionMessage('Themes Manager', 'Theme activated succefully');
    }
}
