<?php

namespace App\Http\Controllers\Inertia\RpmoManagement;

use App\Actions\HarmonizedIpc\CreateHarmonizedIpc;
use App\Actions\HarmonizedIpc\DeleteHarmonizedIpc;
use App\Actions\HarmonizedIpc\UpdateHarmonizedIpc;
use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\HarmonizedIpcDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class HarmonizedIpcController extends Controller
{
    public function index(Request $request): Response
    {
        $rawPerPage = (string) $request->input('perPage', '10');
        $isAll = strtolower($rawPerPage) === 'all';
        $perPageInt = $isAll ? 999999 : max(1, (int) $rawPerPage);

        $filters = [
            'search' => (string) $request->string('search'),
            'year' => (string) $request->string('year', (string) now()->year),
            'category' => (string) $request->string('category'),
            'semester' => (string) $request->string('semester'),
            'position' => (string) $request->string('position'),
            'perPage' => $rawPerPage,
        ];

        $includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);
        $positionId = ctype_digit($filters['position']) ? (int) $filters['position'] : null;

        $paginator = app(HarmonizedIpcDirectory::class)->paginate(
            $positionId,
            $includeStrategicFunction,
            $filters['year'],
            $filters['category'],
            $filters['semester'],
            $filters['search'],
            $perPageInt,
        );

        $user = Auth::user();
        $userProfile = null;

        if ($user) {
            $dbUser = DB::table('users')
                ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
                ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
                ->where('users.id', $user->id)
                ->select([
                    'users.first_name',
                    'users.middle_name',
                    'users.last_name',
                    'users.extension_name',
                    'users.position',
                    'users.designation',
                    DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                    DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
                ])
                ->first();

            if ($dbUser) {
                $userProfile = [
                    'fullName' => trim(($dbUser->last_name ?? '').(filled($dbUser->last_name) ? ', ' : '').collect([$dbUser->first_name, $dbUser->middle_name, $dbUser->extension_name])->filter()->join(' ')),
                    'position' => (string) ($dbUser->position ?? ''),
                    'designation' => (string) ($dbUser->designation ?? ''),
                    'divisionName' => (string) ($dbUser->division_name ?? ''),
                    'sectionName' => (string) ($dbUser->section_name ?? ''),
                ];
            }
        }

        return Inertia::render('RpmoManagement/HarmonizedIpc', [
            'filters' => $filters,
            'userProfile' => $userProfile,
            'includeStrategicFunction' => $includeStrategicFunction,
            'positions' => $this->positions(),
            'years' => $this->years(),
            'categories' => $this->categories($includeStrategicFunction),
            'semesters' => $this->semesters(),
            'perPageOptions' => $this->perPageOptions(),
            'targets' => [
                'data' => array_map(fn ($row) => [
                    'id' => (int) $row->id,
                    'tarid' => (int) $row->tarid,
                    'indicatorId' => (int) $row->ind_id,
                    'kraCategory' => (int) $row->kra_category,
                    'activity' => (string) $row->activity,
                    'semester' => (string) ($row->target_sem_num ?? $row->target_sem ?? ''),
                    'newSemester' => (string) ($row->new_semester ?? $row->target_sem_num ?? ''),
                    'description' => (string) ($row->description ?? ''),
                    'efficiency' => (string) ($row->rg_efficiency_ ?? ''),
                    'quality' => (string) ($row->rg_quality_ ?? ''),
                    'timeliness' => (string) ($row->rg_timeliness_ ?? ''),
                    'movs' => (string) ($row->rg_mov_ ?? ''),
                    'remarks' => (string) ($row->rg_remarks_ ?? ''),
                    'targetStatus' => (int) ($row->target_status ?? 1),
                    'positionId' => $row->harmonized_position_id ? (int) $row->harmonized_position_id : null,
                ], $paginator->items()),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
            ],
            'groups' => $this->groups($paginator),
        ]);
    }

    public function reorder(Request $request): RedirectResponse
    {
        $source = (array) $request->input('source', []);
        $target = (array) $request->input('target', []);

        $move = $this->validatedMove($source, $target);

        if ($move === null) {
            return back()->with('error', __('Unable to move the target. Please try again.'));
        }

        $this->applyMove($move);

        return back()->with('success', __('Target position updated.'));
    }

    public function update(Request $request, UpdateHarmonizedIpc $updater, int $indicatorId, int $rowId): RedirectResponse
    {
        $data = $request->validate([
            'activity' => ['required', 'string'],
            'category' => ['required', 'in:1,2,3'],
            'editRows' => ['required', 'array', 'min:1'],
            'editRows.*.semester' => ['required', 'regex:/^\d+$/'],
            'editRows.*.description' => ['required', 'string'],
            'editRows.*.efficiency' => ['required', 'string'],
            'editRows.*.quality' => ['required', 'string'],
            'editRows.*.timeliness' => ['required', 'string'],
            'editRows.*.movs' => ['required', 'string'],
            'editRows.*.remarks' => ['nullable', 'string'],
            'pendingSubTargets' => ['nullable', 'array'],
            'pendingSubTargets.*.semester' => ['nullable', 'regex:/^\d+$/'],
            'pendingSubTargets.*.description' => ['nullable', 'string'],
            'pendingSubTargets.*.efficiency' => ['nullable', 'string'],
            'pendingSubTargets.*.quality' => ['nullable', 'string'],
            'pendingSubTargets.*.timeliness' => ['nullable', 'string'],
            'pendingSubTargets.*.movs' => ['nullable', 'string'],
            'pendingSubTargets.*.remarks' => ['nullable', 'string'],
        ]);

        $updater->execute(
            $indicatorId,
            $rowId,
            (int) $request->user()->id,
            $data['activity'],
            $data['category'],
            $data['editRows'],
            $data['pendingSubTargets'] ?? []
        );

        return back()->with('success', __('Harmonized IPC updated.'));
    }

    public function store(Request $request, CreateHarmonizedIpc $creator): RedirectResponse
    {
        $data = $request->validate([
            'positionId' => ['nullable', 'integer'],
            'year' => ['required', 'integer'],
            'category' => ['required', 'integer'],
            'activity' => ['required', 'string'],
            'semester' => ['required', 'regex:/^\d+$/'],
            'description' => ['required', 'string'],
            'efficiency' => ['required', 'string'],
            'quality' => ['required', 'string'],
            'timeliness' => ['required', 'string'],
            'movs' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $creator->execute(
            (int) $request->user()->id,
            isset($data['positionId']) ? (int) $data['positionId'] : null,
            (int) $data['year'],
            (int) $data['category'],
            [
                'activity' => $data['activity'],
                'semester' => $data['semester'],
                'description' => $data['description'],
                'efficiency' => $data['efficiency'],
                'quality' => $data['quality'],
                'timeliness' => $data['timeliness'],
                'movs' => $data['movs'],
                'remarks' => $data['remarks'] ?? '',
            ],
        );

        return back()->with('success', __('Harmonized IPC target added.'));
    }

    public function destroy(int $indicatorId, DeleteHarmonizedIpc $deleteHarmonizedIpc): RedirectResponse
    {
        $deleteHarmonizedIpc->execute($indicatorId);

        return back()->with('success', __('Harmonized IPC target deleted.'));
    }

    public function destroyItem(int $itemId, DeleteHarmonizedIpc $deleteHarmonizedIpc): RedirectResponse
    {
        $deleteHarmonizedIpc->executeItem($itemId);

        return back()->with('success', __('Sub-target deleted.'));
    }

    public function storeSubTarget(int $indicatorId): RedirectResponse
    {
        $indicator = DB::table('harmonized_ipc_targets_indicators')->where('id', $indicatorId)->first();

        if (! $indicator) {
            return back()->with('error', __('Target indicator not found.'));
        }

        $maxDisplayOrder = (int) DB::table('harmonized_ipc_targets_indicators_itemlist')
            ->where('ind_id', $indicatorId)
            ->max('display_order');

        DB::table('harmonized_ipc_targets_indicators_itemlist')->insert([
            'ind_id' => $indicatorId,
            'new_semester' => '1',
            'description' => 'New Sub-target',
            'rg_efficiency_' => '',
            'rg_quality_' => '',
            'rg_timeliness_' => '',
            'rg_mov_' => '',
            'rg_remarks_' => '',
            'display_order' => $maxDisplayOrder + 1,
            'indi_status' => 1,
            'date_created' => now(),
        ]);

        return back()->with('success', __('Sub-target added.'));
    }

    private function validatedMove(array $source, array $target): ?array
    {
        $sourceType = (string) ($source['type'] ?? '');
        $targetType = (string) ($target['type'] ?? '');
        $sourceIndicatorId = (int) ($source['indicatorId'] ?? 0);
        $targetIndicatorId = (int) ($target['indicatorId'] ?? 0);
        $sourceItemId = (int) ($source['itemId'] ?? 0);
        $targetItemId = (int) ($target['itemId'] ?? 0);

        if (! in_array($sourceType, ['main', 'sub'], true) || ! in_array($targetType, ['main', 'sub', 'category'], true)) {
            return null;
        }

        $includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);
        $allowedKra = $includeStrategicFunction ? [1, 2, 3] : [2, 3];

        $sourceIndicator = DB::table('harmonized_ipc_targets_indicators')->where('id', $sourceIndicatorId)->first();
        $targetIndicator = $targetIndicatorId > 0
            ? DB::table('harmonized_ipc_targets_indicators')->where('id', $targetIndicatorId)->first()
            : null;
        $targetKra = $targetType === 'category' ? (int) ($target['kra'] ?? 0) : (int) ($targetIndicator->kra_category ?? 0);

        if ($sourceIndicator === null || ! in_array($targetKra, $allowedKra, true) || ($targetType !== 'category' && $targetIndicator === null)) {
            return null;
        }

        if ($sourceType === 'sub') {
            $sourceItem = DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $sourceItemId)->where('ind_id', $sourceIndicatorId)->first();
            $targetItem = $targetItemId > 0 ? DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $targetItemId)->where('ind_id', $targetIndicatorId)->first() : null;

            if ($sourceItem === null || ($targetType !== 'category' && $targetItem === null)) {
                return null;
            }

            if ($targetType === 'category') {
                return null;
            }
        }

        if ($sourceIndicatorId === $targetIndicatorId && ($sourceType === 'main' || $sourceItemId === $targetItemId)) {
            return null;
        }

        return [
            'sourceType' => $sourceType,
            'sourceIndicatorId' => $sourceIndicatorId,
            'sourceItemId' => $sourceItemId,
            'sourceKra' => (int) $sourceIndicator->kra_category,
            'targetType' => $targetType,
            'targetIndicatorId' => $targetIndicatorId,
            'targetItemId' => $targetItemId,
            'targetKra' => $targetKra,
        ];
    }

    private function applyMove(array $move): void
    {
        DB::transaction(function () use ($move): void {
            if ($move['sourceType'] === 'main') {
                $source = DB::table('harmonized_ipc_targets_indicators')->where('id', $move['sourceIndicatorId'])->lockForUpdate()->first();

                if ($source === null) {
                    return;
                }

                if ((int) $source->kra_category !== (int) $move['targetKra']) {
                    $targetOrder = $move['targetIndicatorId'] > 0
                        ? DB::table('harmonized_ipc_targets_indicators')->where('id', $move['targetIndicatorId'])->value('display_order')
                        : null;

                    $queryMax = DB::table('harmonized_ipc_targets_indicators')
                        ->where('target_year', $source->target_year)
                        ->where('kra_category', $move['targetKra']);

                    if (isset($source->harmonized_position_id)) {
                        $queryMax->where('harmonized_position_id', $source->harmonized_position_id);
                    }

                    $newOrder = $targetOrder === null
                        ? ((int) $queryMax->max('display_order')) + 1
                        : (int) $targetOrder;

                    if ($targetOrder !== null) {
                        $incrementQuery = DB::table('harmonized_ipc_targets_indicators')
                            ->where('target_year', $source->target_year)
                            ->where('kra_category', $move['targetKra'])
                            ->where('display_order', '>=', $newOrder);

                        if (isset($source->harmonized_position_id)) {
                            $incrementQuery->where('harmonized_position_id', $source->harmonized_position_id);
                        }

                        $incrementQuery->increment('display_order');
                    }

                    DB::table('harmonized_ipc_targets_indicators')->where('id', $source->id)->update([
                        'kra_category' => $move['targetKra'],
                        'display_order' => $newOrder,
                    ]);

                    return;
                }

                $target = DB::table('harmonized_ipc_targets_indicators')->where('id', $move['targetIndicatorId'])->lockForUpdate()->first();
                if ($target === null) {
                    return;
                }

                DB::table('harmonized_ipc_targets_indicators')->where('id', $source->id)->update(['kra_category' => $target->kra_category, 'display_order' => $target->display_order]);
                DB::table('harmonized_ipc_targets_indicators')->where('id', $target->id)->update(['kra_category' => $source->kra_category, 'display_order' => $source->display_order]);

                return;
            }

            $source = DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $move['sourceItemId'])->lockForUpdate()->first();
            $target = DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $move['targetItemId'])->lockForUpdate()->first();
            if ($source === null || $target === null) {
                return;
            }

            DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $source->id)->update(['ind_id' => $target->ind_id, 'display_order' => $target->display_order]);
            DB::table('harmonized_ipc_targets_indicators_itemlist')->where('id', $target->id)->update(['ind_id' => $source->ind_id, 'display_order' => $source->display_order]);
        });
    }

    private function positions(): array
    {
        return DB::table('lib_harmonized_positions')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name'])
            ->map(fn ($row) => ['value' => (string) $row->id, 'label' => (string) $row->name])
            ->all();
    }

    private function years(): array
    {
        return collect(range(2021, now()->year + 1))
            ->reverse()
            ->values()
            ->map(fn (int $year) => ['value' => (string) $year, 'label' => (string) $year])
            ->all();
    }

    private function categories(bool $includeStrategicFunction): array
    {
        $categories = [
            ['value' => '1', 'label' => 'Strategic Function'],
            ['value' => '2', 'label' => 'Core Function'],
            ['value' => '3', 'label' => 'Support Function'],
        ];

        return $includeStrategicFunction ? $categories : array_values(array_filter($categories, fn ($c) => $c['value'] !== '1'));
    }

    private function semesters(): array
    {
        return [
            ['value' => '1', 'label' => '1st Semester'],
            ['value' => '2', 'label' => '2nd Semester'],
            ['value' => '3', 'label' => 'Both Semester'],
        ];
    }

    private function perPageOptions(): array
    {
        return [
            ['value' => '10', 'label' => '10'],
            ['value' => '20', 'label' => '20'],
            ['value' => '50', 'label' => '50'],
            ['value' => '100', 'label' => '100'],
            ['value' => 'all', 'label' => 'ALL'],
        ];
    }

    private function groups($paginator): array
    {
        return collect($paginator->items())
            ->groupBy(fn ($row) => is_object($row) ? ($row->ind_id ?? $row->indicatorId ?? 0) : ($row['indicatorId'] ?? $row['ind_id'] ?? 0))
            ->map(function ($rows) {
                $first = $rows->first();
                $isObj = is_object($first);

                $indicatorId = (int) ($isObj ? ($first->ind_id ?? $first->indicatorId ?? 0) : ($first['indicatorId'] ?? $first['ind_id'] ?? 0));
                $activity = (string) ($isObj ? ($first->activity ?? '') : ($first['activity'] ?? ''));
                $category = (int) ($isObj ? ($first->kra_category ?? $first->kraCategory ?? 0) : ($first['kraCategory'] ?? $first['kra_category'] ?? 0));
                $targetStatus = (int) ($isObj ? ($first->target_status ?? $first->targetStatus ?? 1) : ($first['targetStatus'] ?? $first['target_status'] ?? 1));

                $mappedRows = $rows->map(function ($row) {
                    if (is_object($row)) {
                        return [
                            'id' => (int) ($row->id ?? 0),
                            'indicatorId' => (int) ($row->ind_id ?? 0),
                            'kraCategory' => (int) ($row->kra_category ?? 0),
                            'activity' => (string) ($row->activity ?? ''),
                            'semester' => (string) ($row->target_sem ?? ''),
                            'newSemester' => (string) ($row->new_semester ?? ''),
                            'description' => (string) ($row->description ?? ''),
                            'efficiency' => (string) ($row->rg_efficiency_ ?? ''),
                            'quality' => (string) ($row->rg_quality_ ?? ''),
                            'timeliness' => (string) ($row->rg_timeliness_ ?? ''),
                            'movs' => (string) ($row->rg_mov_ ?? ''),
                            'remarks' => (string) ($row->rg_remarks_ ?? ''),
                            'targetStatus' => (int) ($row->target_status ?? 1),
                            'positionId' => $row->harmonized_position_id ? (int) $row->harmonized_position_id : null,
                        ];
                    }

                    return (array) $row;
                })->values()->all();

                return [
                    'indicatorId' => $indicatorId,
                    'activity' => $activity,
                    'category' => $category,
                    'targetStatus' => $targetStatus,
                    'rows' => $mappedRows,
                ];
            })
            ->values()
            ->all();
    }
}
