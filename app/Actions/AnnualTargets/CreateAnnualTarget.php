<?php

namespace App\Actions\AnnualTargets;

use Illuminate\Support\Facades\DB;

final class CreateAnnualTarget
{
    /** @param array<string, string> $attributes */
    public function execute(int $userId, int $year, int $category, array $attributes): void
    {
        DB::transaction(function () use ($userId, $year, $category, $attributes): void {
            $now = now();
            $indicatorId = DB::table('ipc_targets_indicators')->insertGetId([
                'target_group_id' => null, 'user_id' => $userId, 'target_sem' => null, 'target_year' => $year,
                'kra_category' => $category,
                'display_order' => ((int) DB::table('ipc_targets_indicators')->where('user_id', $userId)->where('target_year', $year)->where('kra_category', $category)->max('display_order')) + 1,
                'activity' => $attributes['activity'], 'target_status' => 1, 'created_by' => $userId, 'date_created' => $now,
            ]);
            DB::table('ipc_targets_indicators_itemlist')->insert([
                'ind_id' => $indicatorId, 'display_order' => 1, 'new_semester' => (int) $attributes['semester'],
                'description' => $attributes['description'], 'rg_efficiency_' => $attributes['efficiency'],
                'rg_quality_' => $attributes['quality'], 'rg_timeliness_' => $attributes['timeliness'],
                'rg_mov_' => $attributes['movs'], 'rg_remarks_' => $attributes['remarks'],
                'created_by' => $userId, 'modified_by' => $userId, 'indi_status' => 1, 'date_created' => $now,
            ]);
        });
    }
}
