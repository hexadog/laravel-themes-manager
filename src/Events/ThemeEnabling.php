<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Events;

class ThemeEnabling
{
    public array|string $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }
}
