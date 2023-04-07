<?php

namespace Hexadog\ThemesManager\Events;

class ThemeEnabled
{
    /**
     * @var array|string
     */
    public $theme;

    public function __construct($theme)
    {
        $this->theme = $theme;
    }
}
