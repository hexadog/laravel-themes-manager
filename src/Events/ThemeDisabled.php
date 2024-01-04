<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Events;

use Hexadog\ThemesManager\Theme;

class ThemeDisabled
{
    public Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }
}
