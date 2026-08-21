<?php

namespace App\Actions\Users;

use App\Models\Division;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateProfile
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $user, array $attributes): void
    {
        DB::transaction(function () use ($user, $attributes): void {
            $divisionId = $attributes['division_id'] ?: null;
            $sectionId = $attributes['section_id'] ?: null;

            $user->fill($attributes);
            $user->name = $this->fullName($attributes);
            $user->division = $divisionId === null
                ? ''
                : (string) (Division::query()->whereKey($divisionId)->value('division_name') ?? '');
            $user->section = $sectionId === null
                ? ''
                : (string) (Section::query()->whereKey($sectionId)->value('section_name') ?? '');
            $user->save();
        });
    }

    /** @param array<string, mixed> $attributes */
    private function fullName(array $attributes): string
    {
        return trim(collect([
            $attributes['first_name'] ?? '',
            $attributes['middle_name'] ?? '',
            $attributes['last_name'] ?? '',
            $attributes['extension_name'] ?? '',
        ])->filter()->join(' '));
    }
}
