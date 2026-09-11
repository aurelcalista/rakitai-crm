<?php

namespace App\View\Components;

use App\Http\Controllers\CrmController;
use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public array $currentUser;

    /**
     * Create a new component instance.
     */
    public function __construct(public ?string $title = null)
    {
        $this->currentUser = CrmController::getCurrentUser(request());
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('layouts.app', [
            'currentUser' => $this->currentUser,
        ]);
    }
}
