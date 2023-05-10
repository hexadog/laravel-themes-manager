<?php

namespace Hexadog\ThemesManager\Components;

use Illuminate\Support\Facades\View;
use Illuminate\View\Component;

class PageTitle extends Component
{
    /**
     * The page title.
     *
     * @var string
     */
    public $title;

    /**
     * Create the component instance.
     *
     * @param  string  $type
     * @param  string  $message
     * @param  null|mixed  $title
     * @param  mixed  $withAppName
     * @param  mixed  $separator
     * @param  mixed  $invert
     */
    public function __construct($title = null, $withAppName = true, $separator = '-', $invert = false)
    {
        if (View::hasSection('title')) {
            $title = View::getSection('title');
        }

        if (! empty($title) && $withAppName) {
            if ($invert) {
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
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('themes-manager::components.page-title');
    }
}
