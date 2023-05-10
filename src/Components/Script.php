<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Components;

use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\View\Component;

class Script extends Component
{
    /**
     * The script source url.
     */
    public string $source;

    /**
     * Create the component instance.
     */
    public function __construct(?string $src = null, bool $absolute = true)
    {
        if (!is_null($src)) {
            $this->source = ThemesManager::asset($src, $absolute);
        } else {
            $this->source = null;
        }
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('themes-manager::components.script');
    }
}
