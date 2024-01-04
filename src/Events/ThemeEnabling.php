<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Events;

use Hexadog\ThemesManager\Theme;

class ThemeEnabling
{
    public Theme $theme;

    public function __construct(Theme $theme)
    {
        $this->theme = $theme;
    }
}
