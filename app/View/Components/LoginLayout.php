<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class LoginLayout extends Component
{
    public function __construct(
        public string $leftTitle = 'Welcome',
        public string $leftSubtitle = '',
        public string $leftDescription = ''
    ) {
        if ($leftSubtitle === '') {
            $this->leftSubtitle = config('app.name', 'BuchaPro');
        }
        if ($leftDescription === '') {
            $this->leftDescription = __('Meat traceability and compliance for abattoirs, inspectors, and facilities. Sign in to manage your business.');
        }
    }

    public function render(): View
    {
        return view('layouts.login');
    }
}
