<?php

namespace Hexadog\ThemesManager\Console\Commands;

use Hexadog\ThemesManager\Facades\ThemesManager;
use Hexadog\ThemesManager\Console\Commands\AbstractCommand;

class ClearCache extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear themes cache';

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
        if (ThemesManager::clearCache()) {
            $this->sectionMessage('Themes Manager', 'Themes cache cleared succefully');
        } else {
            $this->error("An error occured while clearing themes cache");
        }
    }
}
