<?php

namespace App\View\Components;

use Illuminate\View\Component;

class OtpVerificationInput extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public $uuid;
    public $action;
    public $url;
    public function __construct($uuid, $action, $url)
    {
        $this->uuid = $uuid;
        $this->action = $action;
        $this->url = $url;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.otp-verification-input');
    }
}
