<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

class AnnualTargetDirectory
{
    /** @return LengthAwarePaginator<int, stdClass> */
    public function paginate(
        ?int $userId,
        bool $includeStrategicFunction,
        ?string $year,
        ?string $category,
        ?string $semester,
        ?string $search,
        int $perPage,
        bool $showOnlyDuplicates = false,
    ): LengthAwarePaginator {
        $query = DB::table('ipc_targets_indicators as iti')
            ->join('ipc_targets_indicators_itemlist as itl', 'iti.id', '=', 'itl.ind_id')
            ->when($userId !== null, fn(Builder $q) => $q->where('iti.user_id', $userId))
            ->when(!$includeStrategicFunction, fn(Builder $q) => $q->where('iti.kra_category', '!=', 1))
            ->when(filled($year), fn(Builder $q) => $q->where('iti.target_year', $year))
            ->when(filled($category), fn(Builder $q) => $q->where('iti.kra_category', $category))
            ->when(filled($semester), function (Builder $q) use ($semester): void {
                $sem = (string) $semester;
                if ($sem === '1') {
                    $q->whereIn('itl.new_semester', [1, 3]);
                } elseif ($sem === '2') {
                    $q->whereIn('itl.new_semester', [2, 3]);
                } elseif ($sem === '3') {
                    $q->whereIn('itl.new_semester', [1, 2, 3]);
                }
            })
            ->when($showOnlyDuplicates, function (Builder $q) use ($userId): void {
                $q->whereIn(DB::raw('(iti.user_id, iti.target_year, iti.kra_category, iti.activity)'), function ($sub) use ($userId): void {
                    $sub->select('user_id', 'target_year', 'kra_category', 'activity')
                        ->from('ipc_targets_indicators')
                        ->when($userId !== null, fn($sq) => $sq->where('user_id', $userId))
                        ->groupBy('user_id', 'target_year', 'kra_category', 'activity')
                        ->havingRaw('COUNT(*) > 1');
                });
            })
            ->select([
                'iti.id as indicator_id',
                'iti.target_group_id',
                'iti.user_id',
                'iti.target_sem as target_sem_num',
                DB::raw('(CASE WHEN iti.target_sem = 1 THEN "1st Semester" WHEN iti.target_sem = 2 THEN "2nd Semester" WHEN iti.target_sem = 3 THEN "Both Semester" END) as target_sem'),
                'itl.new_semester',
                'iti.target_year',
                'iti.kra_category',
                'iti.display_order as indicator_display_order',
                'iti.activity',
                'iti.target_status',
                'itl.id',
                'itl.display_order as item_display_order',
                'itl.date_created',
                'itl.description',
                'itl.rg_efficiency_',
                'itl.rg_quality_',
                'itl.rg_timeliness_',
                'itl.rg_mov_',
                'itl.rg_remarks_',
                'itl.indi_status',
            ]);

        if (filled($search)) {
            $like = '%' . trim((string) $search) . '%';
            $query->where(function (Builder $q) use ($like): void {
                $q->where('iti.activity', 'like', $like)
                    ->orWhere('itl.description', 'like', $like)
                    ->orWhere('itl.rg_efficiency_', 'like', $like)
                    ->orWhere('itl.rg_quality_', 'like', $like)
                    ->orWhere('itl.rg_timeliness_', 'like', $like)
                    ->orWhere('itl.rg_mov_', 'like', $like)
                    ->orWhere('itl.rg_remarks_', 'like', $like);
            });
        }

        $query->orderBy('iti.kra_category')
            ->orderByRaw('iti.display_order IS NULL')
            ->orderBy('iti.display_order')
            ->orderBy('iti.date_created')
            ->orderByRaw('itl.display_order IS NULL')
            ->orderBy('itl.display_order')
            ->orderBy('itl.date_created')
            ->orderBy('itl.id');

        $effectivePerPage = $perPage <= 0 ? max(1, (clone $query)->count()) : $perPage;

        return $query->paginate($effectivePerPage);
    }
}
