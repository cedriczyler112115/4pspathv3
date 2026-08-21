<?php

namespace App\Actions\Settings;

use App\Models\ApplicationSetting;

final class UpdateApplicationSettings
{
    public function execute(string $appName, bool $includeStrategicFunction): void
    {
        ApplicationSetting::put('app_name', $appName, 'string', 'Application display name');
        ApplicationSetting::put(
            'include_strategic_function',
            $includeStrategicFunction ? '1' : '0',
            'boolean',
            'Show Strategic Function in Annual Target',
        );
    }
}
