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
        return Inertia::render('Administration/ApplicationSettings', [
            'settings' => [
                'appName' => (string) ApplicationSetting::valueFor('app_name', config('app.name', '4Ps PATH v3')),
                'includeStrategicFunction' => ApplicationSetting::boolean('include_strategic_function', true),
            ],
        ]);
    }

    public function update(Request $request, UpdateApplicationSettings $updateApplicationSettings): RedirectResponse
    {
        $validated = $request->validate([
            'appName' => ['required', 'string', 'max:255'],
            'includeStrategicFunction' => ['boolean'],
        ]);

        $appName = trim($validated['appName']);
        $includeStrategicFunction = (bool) ($validated['includeStrategicFunction'] ?? false);

        $updateApplicationSettings->execute($appName, $includeStrategicFunction);
        config(['app.name' => $appName]);

        return back()->with('success', __('Application settings saved.'));
    }
}
