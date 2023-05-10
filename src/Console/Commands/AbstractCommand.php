<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Console\Commands;

use Hexadog\ThemesManager\Console\Commands\Traits\BlockMessage;
use Hexadog\ThemesManager\Console\Commands\Traits\SectionMessage;
use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\Console\Command;

abstract class AbstractCommand extends Command
{
    use BlockMessage;
    use SectionMessage;

    protected mixed $theme;

    protected function validateName(): void
    {
        $name = $this->argument('name');

        $this->theme = ThemesManager::get($name);
        if (! $this->theme) {
            $this->error("Theme with name {$name} does not exists!");

            exit;
        }
    }
}
