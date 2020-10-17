<?php

namespace Hexadog\ThemesManager\Exceptions;

class ThemeNotActiveException extends \Exception
{
    public function __construct($name = null, $code = 0)
    {
        parent::__construct("Theme with name '{$name}' is not active", $code);
    }
}
