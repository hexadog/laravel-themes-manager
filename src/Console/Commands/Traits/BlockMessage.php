<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Console\Commands\Traits;

trait BlockMessage
{
    public function blockMessage($title, $message, $style = 'info'): void
    {
        $formatter = $this->getHelperSet()->get('formatter');
        $errorMessages = [$title, $message];
        $formattedBlock = $formatter->formatBlock($errorMessages, $style, true);
        $this->line($formattedBlock);
    }
}
