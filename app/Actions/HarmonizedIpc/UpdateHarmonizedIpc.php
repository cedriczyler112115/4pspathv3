<?php

namespace App\Actions\HarmonizedIpc;

use Illuminate\Support\Facades\DB;

final class UpdateHarmonizedIpc
{
    /**
     * @param array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> $rows
     * @param array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> $pendingSubTargets
     */
    public function execute(int $indicatorId, int $rowId, int $userId, string $activity, string $category, array $rows, array $pendingSubTargets = []): bool
    {
        return DB::transaction(function () use ($indicatorId, $rowId, $userId, $activity, $category, $rows, $pendingSubTargets): bool {
            $owned = DB::table('harmonized_ipc_targets_indicators_itemlist as itl')
                ->join('harmonized_ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                ->where('itl.id', $rowId)
                ->where('iti.id', $indicatorId)
                ->exists();

            if (! $owned) {
                return false;
            }

            foreach ($rows as $itemId => $values) {
                DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $itemId)->where('ind_id', $indicatorId)->update([
                    'new_semester' => (int) $values['semester'],
                    'description' => $values['description'],
                    'rg_efficiency_' => $values['efficiency'],
                    'rg_quality_' => $values['quality'],
                    'rg_timeliness_' => $values['timeliness'],
                    'rg_mov_' => $values['movs'],
                    'rg_remarks_' => $values['remarks'],
                ]);
            }

            if (! empty($pendingSubTargets)) {
                $maxDisplayOrder = (int) DB::table('harmonized_ipc_targets_indicators_itemlist')
                    ->where('ind_id', $indicatorId)
                    ->max('display_order');

                foreach ($pendingSubTargets as $pending) {
                    $description = trim((string) ($pending['description'] ?? ''));
                    if ($description === '') {
                        continue;
                    }
                    $maxDisplayOrder++;
                    DB::table('harmonized_ipc_targets_indicators_itemlist')->insert([
                        'ind_id' => $indicatorId,
                        'new_semester' => (int) ($pending['semester'] ?? 1),
                        'description' => $description,
                        'rg_efficiency_' => (string) ($pending['efficiency'] ?? ''),
                        'rg_quality_' => (string) ($pending['quality'] ?? ''),
                        'rg_timeliness_' => (string) ($pending['timeliness'] ?? ''),
                        'rg_mov_' => (string) ($pending['movs'] ?? ''),
                        'rg_remarks_' => (string) ($pending['remarks'] ?? ''),
                        'display_order' => $maxDisplayOrder,
                        'indi_status' => 1,
                        'date_created' => now(),
                    ]);
                }
            }

            DB::table('harmonized_ipc_targets_indicators')->where('id', $indicatorId)->update([
                'activity' => $activity,
                'kra_category' => $category,
            ]);

            return true;
        });
    }
}
