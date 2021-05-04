<?php

namespace Hexadog\ThemesManager\Components;

use Illuminate\View\Component;
use ThemesManager;

class Style extends Component
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
     * @param string $type
     * @param string $message
     * @param mixed  $src
     * @param mixed  $absolutePath
     */
    public function __construct($src, $absolutePath = true)
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
        return view('themes-manager::components.style');
    }
}
