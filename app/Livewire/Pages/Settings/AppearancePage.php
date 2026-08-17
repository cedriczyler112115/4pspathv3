<?php

namespace App\Livewire\Pages\Settings;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Appearance settings')]
class AppearancePage extends Component
{
    public function render(): View
    {
        return view('livewire.pages.settings.appearance-page');
    }
}
