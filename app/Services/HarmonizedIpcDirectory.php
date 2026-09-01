<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use stdClass;

final class HarmonizedIpcDirectory
{
    /** @return LengthAwarePaginator<int, stdClass> */
    public function paginate(
        ?int $harmonizedPositionId,
        bool $includeStrategicFunction,
        string $year,
        string $category,
        string $semester,
        string $search,
        int $perPage,
    ): LengthAwarePaginator {
        return DB::table('harmonized_ipc_targets_indicators as iti')
            ->leftJoin('harmonized_ipc_targets_indicators_itemlist as itl', 'itl.ind_id', '=', 'iti.id')
            ->when($harmonizedPositionId !== null, fn ($query) => $query->where('iti.harmonized_position_id', $harmonizedPositionId))
            ->where('iti.target_status', '<', 4)
            ->where(function ($query): void {
                $query->whereNull('itl.indi_status')
                    ->orWhere('itl.indi_status', '<', 4);
            })
            ->when(! $includeStrategicFunction, fn ($query) => $query->where('iti.kra_category', '!=', 1))
            ->select([
                DB::raw('iti.id as tarid'),
                'itl.ind_id',
                'iti.target_group_id',
                'iti.harmonized_position_id',
                DB::raw('iti.target_sem as target_sem_num'),
                DB::raw('(CASE WHEN iti.target_sem = 1 THEN "1st Semester" WHEN iti.target_sem = 2 THEN "2nd Semester" WHEN iti.target_sem = 3 THEN "Both Semester" END) as target_sem'),
                DB::raw('itl.new_semester as new_semester'),
                'iti.target_year',
                'iti.kra_category',
                DB::raw('iti.display_order as indicator_display_order'),
                'iti.activity',
                'iti.target_status',
                'itl.date_created',
                'itl.id',
                DB::raw('itl.display_order as item_display_order'),
                'itl.description',
                'itl.rg_efficiency_',
                'itl.rg_quality_',
                'itl.rg_timeliness_',
                'itl.rg_mov_',
                'itl.rg_remarks_',
                'itl.indi_status',
            ])
            ->when($year !== '', fn ($query) => $query->where('iti.target_year', $year))
            ->when($category !== '', fn ($query) => $query->where('iti.kra_category', $category))
            ->when($semester !== '', function ($query) use ($semester): void {
                $query->where(function ($semesterQuery) use ($semester): void {
                    if ($semester === '1') {
                        $semesterQuery->whereIn('itl.new_semester', [1, 3])
                            ->orWhereIn('iti.target_sem', [1, 3]);
                    } elseif ($semester === '2') {
                        $semesterQuery->whereIn('itl.new_semester', [2, 3])
                            ->orWhereIn('iti.target_sem', [2, 3]);
                    } elseif ($semester === '3') {
                        $semesterQuery->whereIn('itl.new_semester', [1, 2, 3])
                            ->orWhereIn('iti.target_sem', [1, 2, 3]);
                    } else {
                        $semesterQuery->where('itl.new_semester', $semester)
                            ->orWhere('iti.target_sem', $semester);
                    }
                });
            })
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($searchQuery) use ($like): void {
                    $searchQuery
                        ->where('iti.activity', 'like', $like)
                        ->orWhere('itl.description', 'like', $like)
                        ->orWhere('itl.remarks', 'like', $like);
                });
            })
            ->orderBy('iti.kra_category')
            ->orderByRaw('iti.display_order IS NULL')
            ->orderBy('iti.display_order')
            ->orderBy('iti.date_created')
            ->orderByRaw('itl.display_order IS NULL')
            ->orderBy('itl.display_order')
            ->orderBy('itl.date_created')
            ->orderBy('itl.id')
            ->paginate($perPage);
    }
}
