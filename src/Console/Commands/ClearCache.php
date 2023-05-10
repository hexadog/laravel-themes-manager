<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Console\Commands;

use Hexadog\ThemesManager\Facades\ThemesManager;

class ClearCache extends AbstractCommand
{
    /**
     * The console command name.
     */
    protected string $name = 'theme:cache:clear';

    /**
     * The console command description.
     */
    protected string $description = 'Clear themes cache';

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
    public function handle(): void
    {
        if (ThemesManager::clearCache()) {
            $this->sectionMessage('Themes Manager', 'Themes cache cleared succefully');
        } else {
            $this->error('An error occured while clearing themes cache');
        }
    }
}
