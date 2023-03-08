<?php

namespace Hexadog\ThemesManager\Components;

use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\View\Component;

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
     */
    public function __construct(string $src, bool $absolute = true)
    {
        $this->source = ThemesManager::asset($src, $absolute);
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
