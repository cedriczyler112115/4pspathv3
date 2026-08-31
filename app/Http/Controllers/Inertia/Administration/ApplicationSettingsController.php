<?php

namespace App\Http\Controllers\Inertia\Administration;

use App\Actions\Settings\UpdateApplicationSettings;
use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicationSettingsController extends Controller
{
    public function edit(): Response
    {
        $currentYear = (int) now()->year;
        $years = array_map('strval', range($currentYear + 2, $currentYear - 4));

        return Inertia::render('Administration/ApplicationSettings', [
            'settings' => [
                'appName' => (string) ApplicationSetting::valueFor('app_name', config('app.name', '4Ps PATH v3')),
                'includeStrategicFunction' => ApplicationSetting::boolean('include_strategic_function', true),
                'defaultYear' => ApplicationSetting::defaultYear(),
                'defaultSemester' => ApplicationSetting::defaultSemester(),
            ],
            'years' => $years,
            'semesters' => [
                ['value' => '1', 'label' => '1st Semester'],
                ['value' => '2', 'label' => '2nd Semester'],
            ],
        ]);
    }

    public function update(Request $request, UpdateApplicationSettings $updateApplicationSettings): RedirectResponse
    {
        $validated = $request->validate([
            'appName' => ['required', 'string', 'max:255'],
            'includeStrategicFunction' => ['boolean'],
            'defaultYear' => ['required', 'string', 'max:4'],
            'defaultSemester' => ['required', 'string', 'in:1,2'],
        ]);

        $appName = trim($validated['appName']);
        $includeStrategicFunction = (bool) ($validated['includeStrategicFunction'] ?? false);
        $defaultYear = trim((string) $validated['defaultYear']);
        $defaultSemester = trim((string) $validated['defaultSemester']);

        $updateApplicationSettings->execute($appName, $includeStrategicFunction, $defaultYear, $defaultSemester);
        config(['app.name' => $appName]);

        return back()->with('success', __('Application settings saved.'));
    }
}
