<?php

namespace Hexadog\ThemesManager\Exceptions;

use RuntimeException;

class ThemeLoaderException extends RuntimeException
{
    /**
     * @param string $name
     *
     * @return \Hexadog\ThemesManager\Exceptions\ThemeLoaderException
     */
    public static function duplicate(string $name): self
    {
        return new static(sprintf(
            'A theme named "%s" already exists.',
            $name
        ));
    }
}
