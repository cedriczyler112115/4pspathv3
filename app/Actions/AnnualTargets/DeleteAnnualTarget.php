<?php

namespace App\Actions\AnnualTargets;

use Illuminate\Support\Facades\DB;

final class DeleteAnnualTarget
{
    public function execute(int $indicatorId, int $userId): bool
    {
        return DB::transaction(function () use ($indicatorId, $userId): bool {
            $ownedIndicator = DB::table('ipc_targets_indicators')
                ->where('id', $indicatorId)
                ->where('user_id', $userId)
                ->exists();

            if (! $ownedIndicator) {
                return false;
            }

            DB::table('ipc_targets_indicators_itemlist')->where('ind_id', $indicatorId)->delete();
            DB::table('ipc_targets_indicators')->where('id', $indicatorId)->delete();

            return true;
        });
    }

    public function executeItem(int $itemId, int $userId): bool
    {
        return DB::transaction(function () use ($itemId, $userId): bool {
            $item = DB::table('ipc_targets_indicators_itemlist as itl')
                ->join('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                ->where('itl.id', $itemId)
                ->where('iti.user_id', $userId)
                ->select(['itl.id'])
                ->first();

            if (! $item) {
                return false;
            }

            DB::table('ipc_targets_indicators_itemlist')->where('id', $itemId)->delete();

            return true;
        });
    }
}
