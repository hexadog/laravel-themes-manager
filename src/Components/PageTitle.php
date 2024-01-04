<?php

declare(strict_types=1);

namespace Hexadog\ThemesManager\Components;

use Illuminate\Support\Facades\View;
use Illuminate\View\Component;

class PageTitle extends Component
{
    /**
     * The page title.
     */
    public string $title;

    /**
     * Create the component instance.
     */
    public function __construct(mixed $title = null, mixed $withAppName = true, mixed $separator = '-', mixed $invert = false)
    {
        if (View::hasSection('title')) {
            $title = View::getSection('title');
        }

        if (! empty($title)) {
            if (! filter_var($withAppName, FILTER_VALIDATE_BOOLEAN)) {
                $this->title = $title;
            } elseif ($invert) {
                $this->title = $title . ' ' . trim(e($separator)) . ' ' . config('app.name');
            } else {
                $this->title = config('app.name') . ' ' . trim(e($separator)) . ' ' . $title;
            }
        } else {
            $this->title = config('app.name');
        }
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): \Illuminate\View\View
    {
        return view('themes-manager::components.page-title');
    }
}
