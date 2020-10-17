<?php

namespace Hexadog\ThemesManager\Exceptions;

class ThemeNotFoundException extends \Exception
{
    public function __construct($name = null, $code = 0)
    {
        parent::__construct("Theme with name '{$name}' not found", $code);
    }
}
