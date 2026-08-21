<?php

namespace App\Actions\Users;

use App\Models\UserLevel;

final class ManageUserLevel
{
    /** @param array<string, mixed> $attributes */
    public function save(?int $levelId, array $attributes): void
    {
        $payload = [
            'level_name' => (string) $attributes['level_name'],
            'is_status' => (int) $attributes['is_status'],
        ];

        if ($levelId === null) {
            UserLevel::query()->create($payload);

            return;
        }

        UserLevel::query()->findOrFail($levelId)->update($payload);
    }

    public function delete(int $levelId): void
    {
        UserLevel::query()->findOrFail($levelId)->delete();
    }
}
