<?php

namespace Hexadog\ThemesManager\Components;

use Hexadog\ThemesManager\Facades\ThemesManager;
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
     */
    public function __construct(string $src = null, bool $absolute = true)
    {
        if (! is_null($src)) {
            $this->source = ThemesManager::asset($src, $absolute);
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
