<?php

namespace Hexadog\ThemesManager\Exceptions;

use RuntimeException;

class ComposerLoaderException extends RuntimeException
{
    /**
     * @param string $name
     *
     * @return \Hexadog\ThemesManager\Exceptions\ComposerLoaderException
     */
    public static function duplicate(string $name): self
    {
        return new static(sprintf(
            'A package named "%s" already exists.',
            $name
        ));
    }
}
