<?php

namespace App\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
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
        $searchTerm = filled($search) ? '%'.trim((string) $search).'%' : null;
        $applySemester = static function (Builder $query) use ($semester): void {
            $semesterValue = (string) $semester;

            if ($semesterValue === '1') {
                $query->whereIn('new_semester', [1, 3]);
            } elseif ($semesterValue === '2') {
                $query->whereIn('new_semester', [2, 3]);
            } elseif ($semesterValue === '3') {
                $query->whereIn('new_semester', [1, 2, 3]);
            }
        };

        $indicatorQuery = DB::table('ipc_targets_indicators as iti')
            ->when($userId !== null, fn(Builder $q) => $q->where('iti.user_id', $userId))
            ->when(!$includeStrategicFunction, fn(Builder $q) => $q->where('iti.kra_category', '!=', 1))
            ->when(filled($year), fn(Builder $q) => $q->where('iti.target_year', $year))
            ->when(filled($category), fn(Builder $q) => $q->where('iti.kra_category', $category))
            ->when($showOnlyDuplicates, function (Builder $q) use ($userId): void {
                $q->whereIn(DB::raw('(iti.user_id, iti.target_year, iti.kra_category, iti.activity)'), function ($sub) use ($userId): void {
                    $sub->select('user_id', 'target_year', 'kra_category', 'activity')
                        ->from('ipc_targets_indicators')
                        ->when($userId !== null, fn($sq) => $sq->where('user_id', $userId))
                        ->groupBy('user_id', 'target_year', 'kra_category', 'activity')
                        ->havingRaw('COUNT(*) > 1');
                });
            });

        $indicatorQuery->whereExists(function (Builder $items) use ($applySemester): void {
            $items->selectRaw('1')
                ->from('ipc_targets_indicators_itemlist as visible_items')
                ->whereColumn('visible_items.ind_id', 'iti.id');
            $applySemester($items);
        });

        if ($searchTerm !== null) {
            $indicatorQuery->where(function (Builder $filter) use ($searchTerm, $applySemester): void {
                $filter->where('iti.activity', 'like', $searchTerm)
                    ->orWhereExists(function (Builder $items) use ($searchTerm, $applySemester): void {
                        $items->selectRaw('1')
                            ->from('ipc_targets_indicators_itemlist as search_items')
                            ->whereColumn('search_items.ind_id', 'iti.id')
                            ->where(function (Builder $fields) use ($searchTerm): void {
                                $fields->where('search_items.description', 'like', $searchTerm)
                                    ->orWhere('search_items.rg_efficiency_', 'like', $searchTerm)
                                    ->orWhere('search_items.rg_quality_', 'like', $searchTerm)
                                    ->orWhere('search_items.rg_timeliness_', 'like', $searchTerm)
                                    ->orWhere('search_items.rg_mov_', 'like', $searchTerm)
                                    ->orWhere('search_items.rg_remarks_', 'like', $searchTerm);
                            });
                        $applySemester($items);
                    });
            });
        }

        $indicatorQuery->select('iti.id')
            ->orderBy('iti.kra_category')
            ->orderByRaw('iti.display_order IS NULL')
            ->orderBy('iti.display_order')
            ->orderBy('iti.date_created')
            ->orderBy('iti.id');

        if ($perPage <= 0 || $perPage >= 99999) {
            $indicatorIds = $indicatorQuery->pluck('iti.id')->map(static fn(mixed $id): int => (int) $id);
            $totalCount = max(1, $indicatorIds->count());
            $indicatorPage = new Paginator(
                $indicatorIds->map(static fn(int $id): object => (object) ['id' => $id]),
                $totalCount,
                $totalCount,
                1,
                ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'],
            );
        } else {
            $indicatorPage = $indicatorQuery->paginate($perPage);
            $indicatorIds = $indicatorPage->getCollection()->pluck('id')->map(static fn(mixed $id): int => (int) $id);
        }

        if ($indicatorIds->isEmpty()) {
            $indicatorPage->setCollection(collect());

            return $indicatorPage;
        }

        $rows = DB::table('ipc_targets_indicators as iti')
            ->join('ipc_targets_indicators_itemlist as itl', 'iti.id', '=', 'itl.ind_id')
            ->whereIn('iti.id', $indicatorIds)
            ->when(filled($semester), function (Builder $query) use ($applySemester): void {
                $applySemester($query);
            })
            ->when($searchTerm !== null, function (Builder $query) use ($searchTerm): void {
                $query->where(function (Builder $fields) use ($searchTerm): void {
                    $fields->where('iti.activity', 'like', $searchTerm)
                        ->orWhere('itl.description', 'like', $searchTerm)
                        ->orWhere('itl.rg_efficiency_', 'like', $searchTerm)
                        ->orWhere('itl.rg_quality_', 'like', $searchTerm)
                        ->orWhere('itl.rg_timeliness_', 'like', $searchTerm)
                        ->orWhere('itl.rg_mov_', 'like', $searchTerm)
                        ->orWhere('itl.rg_remarks_', 'like', $searchTerm);
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
            ])
            ->orderBy('iti.kra_category')
            ->orderByRaw('iti.display_order IS NULL')
            ->orderBy('iti.display_order')
            ->orderBy('iti.date_created')
            ->orderByRaw('itl.display_order IS NULL')
            ->orderBy('itl.display_order')
            ->orderBy('itl.date_created')
            ->orderBy('itl.id');

        $indicatorPage->setCollection($rows->get());

        return $indicatorPage;
    }
}
