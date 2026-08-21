<?php

namespace App\Actions\HarmonizedIpc;

use Illuminate\Support\Facades\DB;

final class DeleteHarmonizedIpc
{
    public function execute(int $indicatorId): bool
    {
        return DB::transaction(function () use ($indicatorId): bool {
            $ownedIndicator = DB::table('harmonized_ipc_targets_indicators')
                ->where('id', $indicatorId)
                ->exists();

            if (! $ownedIndicator) {
                return false;
            }

            DB::table('harmonized_ipc_targets_indicators_itemlist')->where('ind_id', $indicatorId)->delete();
            DB::table('harmonized_ipc_targets_indicators')->where('id', $indicatorId)->delete();

            return true;
        });
    }

    public function executeItem(int $itemId): bool
    {
        return DB::transaction(function () use ($itemId): bool {
            $item = DB::table('harmonized_ipc_targets_indicators_itemlist as itl')
                ->join('harmonized_ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                ->where('itl.id', $itemId)
                ->select(['itl.id'])
                ->first();

            if (! $item) {
                return false;
            }

            DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $itemId)->delete();

            return true;
        });
    }
}
