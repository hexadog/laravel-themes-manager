<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Components;

use Hexadog\ThemesManager\Facades\ThemesManager;
use Illuminate\View\Component;

class Style extends Component
{
    /**
     * The style source url.
     */
    public ?string $source = null;

    /**
     * Create the component instance.
     */
    public function __construct(string $src, bool $absolute = true)
    {
        $this->source = ThemesManager::asset($src, $absolute);
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('themes-manager::components.style');
    }
}
