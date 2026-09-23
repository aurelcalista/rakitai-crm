<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class MobileFormLayout extends Component
{
    public function __construct(
        public ?string $title = 'Form Lapangan CRM UCIC',
        public ?string $pageHeader = 'Form Lapangan'
    ) {}

    public function render(): View
    {
        return view('layouts.mobile-form', [
            'title' => $this->title,
            'pageHeader' => $this->pageHeader,
        ]);
    }
}
