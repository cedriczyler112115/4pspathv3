<?php

namespace App\Livewire\Pages\Administration;

use App\Actions\Settings\UpdateApplicationSettings;
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

    public string $defaultYear = '';

    public string $defaultSemester = '1';

    public function mount(): void
    {
        $this->appName = (string) ApplicationSetting::valueFor('app_name', config('app.name', '4Ps PATH v3'));
        $this->includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);
        $this->defaultYear = ApplicationSetting::defaultYear();
        $this->defaultSemester = ApplicationSetting::defaultSemester();
    }

    public function save(UpdateApplicationSettings $updateApplicationSettings): void
    {
        $validated = $this->validate([
            'appName' => ['required', 'string', 'max:255'],
            'includeStrategicFunction' => ['boolean'],
            'defaultYear' => ['nullable', 'string', 'max:4'],
            'defaultSemester' => ['nullable', 'string', 'in:1,2'],
        ]);

        $this->appName = trim($validated['appName']);
        $this->defaultYear = trim((string) ($validated['defaultYear'] ?? $this->defaultYear));
        $this->defaultSemester = trim((string) ($validated['defaultSemester'] ?? $this->defaultSemester));

        $updateApplicationSettings->execute(
            $this->appName,
            $validated['includeStrategicFunction'],
            $this->defaultYear,
            $this->defaultSemester,
        );
        config(['app.name' => $this->appName]);

        Flux::toast(variant: 'success', text: __('Application settings saved.'));
    }

    public function render(): View
    {
        return view('livewire.pages.administration.application-settings-page');
    }
}
