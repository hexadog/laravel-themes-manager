<?php

namespace Hexadog\ThemesManager\Console\Commands;

use Hexadog\ThemesManager\Facades\ThemesManager;

class Cache extends AbstractCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'theme:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create themes cache';

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
        if (ThemesManager::buildCache()) {
            $this->sectionMessage('Themes Manager', 'Themes cache created succefully');
        } else {
            $this->error('An error occured while creating themes cache');
        }
    }
}
