<?php

namespace App\Livewire\Pages\Administration;

use App\Models\ApplicationSetting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Application Settings')]
class ApplicationSettingsPage extends Component
{
    public string $appName = '';

    public bool $includeStrategicFunction = true;

    public function mount(): void
    {
        $this->appName = (string) ApplicationSetting::valueFor('app_name', config('app.name', '4Ps PATH v3'));
        $this->includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);
    }

    public function save(): void
    {
        $validated = $this->validate([
            'appName' => ['required', 'string', 'max:255'],
            'includeStrategicFunction' => ['boolean'],
        ]);

        ApplicationSetting::put('app_name', trim($validated['appName']), 'string', 'Application display name');
        ApplicationSetting::put(
            'include_strategic_function',
            $validated['includeStrategicFunction'] ? '1' : '0',
            'boolean',
            'Show Strategic Function in Annual Target',
        );

        $this->appName = trim($validated['appName']);
        config(['app.name' => $this->appName]);

        Flux::toast(variant: 'success', text: __('Application settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.pages.administration.application-settings-page');
    }
}
