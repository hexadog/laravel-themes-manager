<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Events;

class ThemeDisabled
{
    public array|string $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }
}
