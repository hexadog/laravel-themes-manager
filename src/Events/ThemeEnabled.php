<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Events;

class ThemeEnabled
{
    public array|string $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }
}
