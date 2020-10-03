<?php

namespace Hexadog\ThemesManager\Facades;

use Illuminate\Support\Facades\Facade;

class ThemeManager extends Facade
{
	/**
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'themes-manager';
	}
}
