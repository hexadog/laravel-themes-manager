<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Console\Commands\Traits;

trait BlockMessage
{
    public function blockMessage($title, $message, $style = 'info'): void
    {
        $errorMessages = [$title, $message];
        $formattedBlock = $this->getHelperSet()->get('formatter')->formatBlock($errorMessages, $style, true);

        $this->line($formattedBlock);
    }
}
