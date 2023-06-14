<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Facades;

use Illuminate\Support\Facades\Facade;

class ThemesManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'themes-manager';
    }
}
