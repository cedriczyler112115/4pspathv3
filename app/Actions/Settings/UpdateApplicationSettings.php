<?php

namespace App\Actions\Settings;

use App\Models\ApplicationSetting;

final class UpdateApplicationSettings
{
    public function execute(string $appName, bool $includeStrategicFunction, string $defaultYear = '', string $defaultSemester = '1'): void
    {
        ApplicationSetting::put('app_name', $appName, 'string', 'Application display name');
        ApplicationSetting::put(
            'include_strategic_function',
            $includeStrategicFunction ? '1' : '0',
            'boolean',
            'Show Strategic Function in Annual Target',
        );

        if ($defaultYear !== '') {
            ApplicationSetting::put('default_year', $defaultYear, 'string', 'Default year for filters and target forms');
        }

        if ($defaultSemester !== '') {
            ApplicationSetting::put('default_semester', $defaultSemester, 'string', 'Default semester (1 or 2) for filters and target forms');
        }
    }
}
