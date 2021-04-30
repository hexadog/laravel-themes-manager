<?php

namespace Hexadog\ThemesManager\Components;

use Illuminate\View\Component;
use ThemesManager;

class Image extends Component
{
    /**
     * The style source url.
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
    public function __construct($src, $absolutePath = true)
    {
        $this->source = ThemesManager::asset($src, $absolutePath);
    }

    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('themes-manager::components.image');
    }
}
