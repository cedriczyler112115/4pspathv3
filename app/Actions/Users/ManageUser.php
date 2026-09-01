<?php

namespace App\Actions\Users;

use Illuminate\Support\Facades\DB;
use stdClass;

final class ManageUser
{
    public function find(int $userId): ?stdClass
    {
        return DB::table('users')->where('id', $userId)->first();
    }

    /** @param array<string, mixed> $attributes */
    public function update(int $userId, array $attributes): void
    {
        DB::table('users')->where('id', $userId)->update([
            'last_name' => $attributes['editLastName'] ?: null,
            'first_name' => $attributes['editFirstName'] ?: null,
            'middle_name' => $attributes['editMiddleName'] ?: null,
            'extension_name' => $attributes['editExtensionName'] ?: null,
            'position' => $attributes['editPosition'] ?: null,
            'designation' => $attributes['editDesignation'] ?: null,
            'division_id' => $attributes['editDivision'] !== '' ? $attributes['editDivision'] : null,
            'section_id' => $attributes['editSection'] !== '' ? $attributes['editSection'] : null,
            'supervisor_id' => $attributes['editSupervisorId'] !== '' ? $attributes['editSupervisorId'] : null,
            'user_level_id' => isset($attributes['editUserLevelId']) && $attributes['editUserLevelId'] !== '' ? (int) $attributes['editUserLevelId'] : null,
            'contact_number' => $attributes['editContactNumber'] ?: null,
            'is_supervisor' => (int) $attributes['editIsSupervisor'],
            'can_scorecard' => isset($attributes['editCanScorecard']) ? ((bool) $attributes['editCanScorecard'] ? 1 : 0) : 0,
            'updated_at' => now(),
        ]);
        app(\App\Services\SidebarMenuTree::class)->forget();
    }

    public function delete(int $userId): void
    {
        DB::table('users')->where('id', $userId)->delete();
    }

    public function toggleStatus(int $userId): ?int
    {
        return DB::transaction(function () use ($userId): ?int {
            $user = DB::table('users')->lockForUpdate()->where('id', $userId)->first(['is_status']);

            if ($user === null) {
                return null;
            }

            $status = (int) $user->is_status === 1 ? 0 : 1;

            DB::table('users')->where('id', $userId)->update([
                'is_status' => $status,
                'date_modified' => now(),
                'activated_at' => $status === 1 ? now() : null,
                'deactivated_at' => $status === 0 ? now() : null,
            ]);

            return $status;
        });
    }
}
