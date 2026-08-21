<?php

namespace App\Actions\HarmonizedIpc;

use Illuminate\Support\Facades\DB;

final class UpdateHarmonizedIpc
{
    /** @param array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> $rows */
    public function execute(int $indicatorId, int $rowId, int $userId, string $activity, string $category, array $rows): bool
    {
        return DB::transaction(function () use ($indicatorId, $rowId, $userId, $activity, $category, $rows): bool {
            $owned = DB::table('harmonized_ipc_targets_indicators_itemlist as itl')
                ->join('harmonized_ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                ->where('itl.id', $rowId)
                ->where('iti.id', $indicatorId)
                ->exists();
            if (! $owned) return false;
            foreach ($rows as $itemId => $values) {
                DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $itemId)->where('ind_id', $indicatorId)->update([
                    'new_semester' => (int) $values['semester'], 'description' => $values['description'], 'rg_efficiency_' => $values['efficiency'], 'rg_quality_' => $values['quality'], 'rg_timeliness_' => $values['timeliness'], 'rg_mov_' => $values['movs'], 'rg_remarks_' => $values['remarks'],
                ]);
            }
            DB::table('harmonized_ipc_targets_indicators')->where('id', $indicatorId)->update(['activity' => $activity, 'kra_category' => $category]);
            return true;
        });
    }
}
