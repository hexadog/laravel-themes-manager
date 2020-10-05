<?php

namespace Hexadog\ThemesManager\Facades;

use Illuminate\Support\Facades\Facade;

class ThemesManager extends Facade
{
	/**
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'themes-manager';
	}
}
