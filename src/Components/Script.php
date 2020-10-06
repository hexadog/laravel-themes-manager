<?php

namespace Hexadog\ThemesManager\Components;

use ThemesManager;
use Illuminate\View\Component;

class Script extends Component
{
	/**
	 * The script source url.
	 *
	 * @var string
	 */
	public $source;

	/**
	 * Create the component instance.
	 *
	 * @param  string  $type
	 * @param  string  $message
	 * @return void
	 */
	public function __construct($src = null, $absolutePath = true)
	{
		if (!is_null($src)) {
			$this->source = ThemesManager::asset($src, $absolutePath);
		} else {
			$this->source = null;
		}
	}

	/**
	 * Get the view / contents that represents the component.
	 *
	 * @return \Illuminate\View\View
	 */
	public function render()
	{
		return view('themes-manager::components.script');
	}
}
