<?php

namespace App\Http\Controllers\Inertia;

use App\Actions\AnnualTargets\CreateAnnualTarget;
use App\Actions\AnnualTargets\DeleteAnnualTarget;
use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\AnnualTargetDirectory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AnnualTargetController extends Controller
{
    public function index(Request $request): Response
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);

        $rawPerPage = (string) $request->input('perPage', '10');
        $isAll = strtolower($rawPerPage) === 'all' || $rawPerPage === '-1';
        $perPageInt = $isAll ? 999999 : max(1, (int) $rawPerPage);

        $selectedYear = $request->filled('year') ? (string) $request->input('year') : ApplicationSetting::defaultYear();

        $filters = [
            'search' => (string) $request->input('search', ''),
            'year' => $selectedYear,
            'category' => (string) $request->input('category', ''),
            'semester' => (string) $request->input('semester', ''),
            'perPage' => $rawPerPage,
            'showOnlyDuplicates' => $request->boolean('duplicates'),
        ];

        $userProfile = DB::table('users')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->where('users.id', $userId)
            ->select([
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.position',
                'users.designation',
                DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
            ])
            ->first();

        $fullName = $userProfile ? trim(($userProfile->last_name ?? '').(filled($userProfile->last_name) ? ', ' : '').collect([$userProfile->first_name, $userProfile->middle_name])->filter()->join(' ')) : '';

        $directory = app(AnnualTargetDirectory::class);
        $paginated = $directory->paginate(
            $userId,
            $includeStrategicFunction,
            $filters['year'],
            $filters['category'],
            $filters['semester'],
            $filters['search'],
            $perPageInt,
            $filters['showOnlyDuplicates']
        );

        $groups = [];
        foreach ($paginated->items() as $row) {
            $indId = (int) $row->indicator_id;
            if (! isset($groups[$indId])) {
                $groups[$indId] = [
                    'indicatorId' => $indId,
                    'targetGroupId' => $row->target_group_id,
                    'targetYear' => (int) $row->target_year,
                    'kraCategory' => (int) $row->kra_category,
                    'displayOrder' => $row->indicator_display_order,
                    'activity' => (string) $row->activity,
                    'targetStatus' => (int) $row->target_status,
                    'rows' => [],
                ];
            }
            $groups[$indId]['rows'][] = [
                'id' => (int) $row->id,
                'indicatorId' => $indId,
                'newSemester' => (string) ($row->new_semester ?? $row->target_sem_num ?? ''),
                'semester' => (string) ($row->target_sem_num ?? $row->target_sem ?? ''),
                'displayOrder' => $row->item_display_order,
                'description' => (string) $row->description,
                'efficiency' => (string) $row->rg_efficiency_,
                'quality' => (string) $row->rg_quality_,
                'timeliness' => (string) $row->rg_timeliness_,
                'movs' => (string) $row->rg_mov_,
                'remarks' => (string) $row->rg_remarks_,
                'indiStatus' => (int) $row->indi_status,
            ];
        }

        $currentYear = now()->year;
        $years = collect(range(2021, $currentYear + 1))
            ->reverse()
            ->values()
            ->map(fn (int $y) => ['value' => (string) $y, 'label' => (string) $y])
            ->all();

        $categories = [
            ['value' => '1', 'label' => 'Strategic Function', 'enabled' => $includeStrategicFunction],
            ['value' => '2', 'label' => 'Core Function', 'enabled' => true],
            ['value' => '3', 'label' => 'Support Function', 'enabled' => true],
        ];

        $semesters = [
            ['value' => '1', 'label' => '1st Semester'],
            ['value' => '2', 'label' => '2nd Semester'],
            ['value' => '3', 'label' => 'Both Semester'],
        ];

        $isLocked = DB::table('ipc_targets_indicators')
            ->where('user_id', $userId)
            ->where('target_year', $filters['year'])
            ->where('target_status', 3)
            ->exists();

        $totalIndicators = $paginated->total();
        $currentPage = $paginated->currentPage();
        $perPageCount = count($groups);
        $fromIndicator = $totalIndicators > 0 ? (($currentPage - 1) * ($isAll ? $totalIndicators : $perPageInt)) + 1 : 0;
        $toIndicator = $totalIndicators > 0 ? min($totalIndicators, $fromIndicator + $perPageCount - 1) : 0;

        return Inertia::render('AnnualTarget/AnnualTarget', [
            'filters' => $filters,
            'includeStrategicFunction' => $includeStrategicFunction,
            'userProfile' => [
                'fullName' => $fullName,
                'position' => $userProfile->position ?? '',
                'designation' => $userProfile->designation ?? '',
                'divisionName' => $userProfile->division_name ?? '',
                'sectionName' => $userProfile->section_name ?? '',
            ],
            'isLocked' => $isLocked,
            'years' => $years,
            'categories' => array_values(array_filter($categories, fn ($c) => $c['enabled'])),
            'semesters' => $semesters,
            'perPageOptions' => [
                ['value' => '10', 'label' => '10'],
                ['value' => '20', 'label' => '20'],
                ['value' => '50', 'label' => '50'],
                ['value' => '100', 'label' => '100'],
                ['value' => 'all', 'label' => 'ALL'],
            ],
            'targets' => [
                'from' => $fromIndicator,
                'to' => $toIndicator,
                'total' => $totalIndicators,
                'currentPage' => $currentPage,
                'lastPage' => $paginated->lastPage(),
            ],
            'groups' => array_values($groups),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $validated = $request->validate([
            'year' => ['required', 'integer'],
            'category' => ['required', 'integer', 'in:1,2,3'],
            'activity' => ['required', 'string'],
            'semester' => ['required', 'integer', 'in:1,2,3'],
            'description' => ['required', 'string'],
            'efficiency' => ['required', 'string'],
            'quality' => ['required', 'string'],
            'timeliness' => ['required', 'string'],
            'movs' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        app(CreateAnnualTarget::class)->execute($userId, (int) $validated['year'], (int) $validated['category'], [
            'activity' => $validated['activity'],
            'semester' => (string) $validated['semester'],
            'description' => $validated['description'],
            'efficiency' => $validated['efficiency'],
            'quality' => $validated['quality'],
            'timeliness' => $validated['timeliness'],
            'movs' => $validated['movs'],
            'remarks' => $validated['remarks'] ?? '',
        ]);

        return back()->with('success', __('Annual target added successfully.'));
    }

    public function reorder(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $source = (array) $request->input('source', []);
        $target = (array) $request->input('target', []);

        $move = $this->validatedMove($userId, $source, $target);

        if ($move === null) {
            return back()->with('error', __('Unable to move the target. Please try again.'));
        }

        $this->applyMove($move);

        return back()->with('success', __('Target position updated.'));
    }

    public function storeSubTarget(Request $request, int $indicatorId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $indicator = DB::table('ipc_targets_indicators')
            ->where('id', $indicatorId)
            ->where('user_id', $userId)
            ->first();

        if (! $indicator) {
            return back()->with('error', __('Target indicator not found.'));
        }

        $maxDisplayOrder = (int) DB::table('ipc_targets_indicators_itemlist')
            ->where('ind_id', $indicatorId)
            ->max('display_order');

        DB::table('ipc_targets_indicators_itemlist')->insert([
            'ind_id' => $indicatorId,
            'new_semester' => 1,
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

    public function update(Request $request, int $indicatorId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $validated = $request->validate([
            'activity' => ['required', 'string'],
            'category' => ['required', 'integer', 'in:1,2,3'],
            'editRows' => ['required', 'array'],
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

        DB::transaction(function () use ($userId, $indicatorId, $validated): void {
            DB::table('ipc_targets_indicators')
                ->where('id', $indicatorId)
                ->where('user_id', $userId)
                ->update([
                    'activity' => $validated['activity'],
                    'kra_category' => $validated['category'],
                ]);

            foreach ($validated['editRows'] as $itemId => $values) {
                DB::table('ipc_targets_indicators_itemlist')
                    ->where('id', (int) $itemId)
                    ->where('ind_id', $indicatorId)
                    ->update([
                        'new_semester' => (int) $values['semester'],
                        'description' => $values['description'],
                        'rg_efficiency_' => $values['efficiency'],
                        'rg_quality_' => $values['quality'],
                        'rg_timeliness_' => $values['timeliness'],
                        'rg_mov_' => $values['movs'],
                        'rg_remarks_' => $values['remarks'] ?? '',
                    ]);
            }

            if (! empty($validated['pendingSubTargets'])) {
                $maxDisplayOrder = (int) DB::table('ipc_targets_indicators_itemlist')
                    ->where('ind_id', $indicatorId)
                    ->max('display_order');

                foreach ($validated['pendingSubTargets'] as $pending) {
                    $description = trim((string) ($pending['description'] ?? ''));
                    if ($description === '') {
                        continue;
                    }
                    $maxDisplayOrder++;
                    DB::table('ipc_targets_indicators_itemlist')->insert([
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
        });

        return back()->with('success', __('Annual target updated successfully.'));
    }

    public function destroyIndicator(int $indicatorId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $targetYear = DB::table('ipc_targets_indicators')
            ->where('id', $indicatorId)
            ->where('user_id', $userId)
            ->value('target_year');

        if ($targetYear && DB::table('ipc_semester')->where('user_id', $userId)->where('year', $targetYear)->exists()) {
            return back()->with('error', __('Cannot delete target. A rating record for year :year exists in My Ratings.', ['year' => $targetYear]));
        }

        if (app(DeleteAnnualTarget::class)->execute($indicatorId, $userId)) {
            return back()->with('success', __('Annual target deleted.'));
        }

        return back()->with('error', __('Failed to delete target.'));
    }

    public function destroyItem(int $itemId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $targetYear = DB::table('ipc_targets_indicators_itemlist as itl')
            ->join('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
            ->where('itl.id', $itemId)
            ->where('iti.user_id', $userId)
            ->value('iti.target_year');

        if ($targetYear && DB::table('ipc_semester')->where('user_id', $userId)->where('year', $targetYear)->exists()) {
            return back()->with('error', __('Cannot delete sub-target. A rating record for year :year exists in My Ratings.', ['year' => $targetYear]));
        }

        if (app(DeleteAnnualTarget::class)->executeItem($itemId, $userId)) {
            return back()->with('success', __('Sub-target deleted.'));
        }

        return back()->with('error', __('Failed to delete sub-target.'));
    }

    public function lock(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $selectedYear = (int) $request->input('year', now()->year);
        if ($selectedYear <= 0) {
            return back()->with('error', __('Please select a specific year before saving and locking annual targets.'));
        }

        DB::transaction(function () use ($userId, $selectedYear): void {
            $targetIds = DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('target_year', $selectedYear)
                ->pluck('id');

            // STEP 1: Update target_status = 3 and indi_status = 3
            DB::table('ipc_targets_indicators')
                ->whereIn('id', $targetIds)
                ->where('target_status', 1)
                ->update(['target_status' => 3]);

            DB::table('ipc_targets_indicators_itemlist')
                ->whereIn('ind_id', $targetIds)
                ->where('indi_status', 1)
                ->update(['indi_status' => 3]);

            $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

            // STEP 2: Query linked targets & itemlists using right join
            $linkedRows = DB::table('ipc_targets_indicators as iti')
                ->rightJoin('ipc_targets_indicators_itemlist as itl', 'iti.id', '=', 'itl.ind_id')
                ->whereIn('iti.id', $targetIds)
                ->where('iti.user_id', $userId)
                ->where('iti.target_year', $selectedYear)
                ->where('iti.target_status', 3)
                ->where('itl.indi_status', 3)
                ->select(
                    'iti.id as indicator_id',
                    'iti.kra_category',
                    'iti.display_order as indicator_display_order',
                    'iti.activity',
                    'itl.id as item_id',
                    'itl.display_order as item_display_order',
                    'itl.new_semester',
                    'itl.description',
                    'itl.quantity',
                    'itl.quality',
                    'itl.timeliness',
                    'itl.rg_efficiency_',
                    'itl.rg_quality_',
                    'itl.rg_timeliness_',
                    'itl.rg_ratingperiod_',
                    'itl.rg_mov_',
                    'itl.rg_remarks_',
                    'itl.remarks as item_remarks'
                )
                ->get();

            $groupedByIndicator = $linkedRows->groupBy('indicator_id');

            foreach ([1, 2] as $semester) {
                $existingSem = DB::table('ipc_semester')
                    ->where('user_id', $userId)
                    ->where('year', $selectedYear)
                    ->where('semester', $semester)
                    ->first();

                if ($existingSem) {
                    $ipcSemesterId = $existingSem->id;
                    DB::table('ipc_semester')
                        ->where('id', $ipcSemesterId)
                        ->update([
                            'sem_status' => 1,
                            'modified_by' => $userId,
                            'last_date_modified' => $nowManila,
                        ]);
                } else {
                    $ipcSemesterId = DB::table('ipc_semester')->insertGetId([
                        'year' => $selectedYear,
                        'semester' => $semester,
                        'user_id' => $userId,
                        'sem_status' => 1,
                        'created_by' => $userId,
                        'date_created' => $nowManila,
                        'modified_by' => $userId,
                        'last_date_modified' => $nowManila,
                    ]);
                }

                foreach ($groupedByIndicator as $indicatorId => $items) {
                    if (empty($indicatorId)) {
                        continue;
                    }

                    $firstItem = $items->first();

                    $matchingItems = $items->filter(function ($item) use ($semester) {
                        $rawSem = $item->new_semester ?? 0;
                        $sem = 0;
                        if (is_numeric($rawSem)) {
                            $sem = (int) $rawSem;
                        } elseif (is_string($rawSem)) {
                            $lower = strtolower($rawSem);
                            if (str_contains($lower, '1st') || $lower === '1') {
                                $sem = 1;
                            } elseif (str_contains($lower, '2nd') || $lower === '2') {
                                $sem = 2;
                            } elseif (str_contains($lower, 'both') || $lower === '3') {
                                $sem = 3;
                            }
                        }

                        if ($semester === 1) {
                            return $sem === 1 || $sem === 3;
                        }

                        if ($semester === 2) {
                            return $sem === 2 || $sem === 3;
                        }

                        return false;
                    });

                    if ($matchingItems->isEmpty()) {
                        continue;
                    }

                    $semTargetId = (int) DB::table('ipc_sem_targets_indicator')->insertGetId([
                        'ipc_target_indicator_id' => $firstItem->indicator_id,
                        'semester_id' => $ipcSemesterId,
                        'kra_category' => $firstItem->kra_category,
                        'display_order' => $firstItem->indicator_display_order ?? null,
                        'activity' => $firstItem->activity,
                        'verified' => null,
                        'verified_by' => null,
                        'date_verified' => null,
                        'remarks' => null,
                        'target_status' => 1,
                        'created_by' => $userId,
                        'date_created' => $nowManila,
                        'modified_by' => $userId,
                        'last_date_modified' => $nowManila,
                        'target_from' => $userId,
                    ]);

                    foreach ($matchingItems as $itl) {
                        DB::table('ipc_sem_targets_indicator_itemlist')->insert([
                            'target_orig_id' => $itl->item_id,
                            'sem_target_id' => $semTargetId,
                            'display_order' => $itl->item_display_order ?? null,
                            'sem_item_id' => $ipcSemesterId,
                            'new_semester' => $itl->new_semester,
                            'description' => $itl->description,
                            'actual_accomp' => null,
                            'weight' => null,
                            'quantity' => null,
                            'quality' => null,
                            'timeliness' => null,
                            'a_quantity' => null,
                            'a_quality' => null,
                            'a_timeliness' => null,
                            'quantity_score' => null,
                            'quality_score' => null,
                            'timeliness_score' => null,
                            'average' => null,
                            'weighted_average' => null,
                            'scorecard_quantity_score' => null,
                            'scorecard_quality_score' => null,
                            'scorecard_timeliness_score' => null,
                            'scorecard_remarks' => null,
                            'scorecard_created' => null,
                            'scorecard_remarks_created' => null,
                            'na_quality' => null,
                            'na_timeliness' => null,
                            'na_quantity' => null,
                            'rg_quantity' => $itl->rg_efficiency_ ?? $itl->quantity ?? null,
                            'rg_quality' => $itl->rg_quality_ ?? $itl->quality ?? null,
                            'rg_timeliness' => $itl->rg_timeliness_ ?? $itl->timeliness ?? null,
                            'rg_ratingperiod' => $itl->rg_ratingperiod_ ?? null,
                            'rg_movs' => $itl->rg_mov_ ?? null,
                            'rg_remarks' => $itl->rg_remarks_ ?? $itl->item_remarks ?? null,
                            'remarks' => 1,
                            'target_remarks' => null,
                            'target_movs' => null,
                            'supervisor_remarks' => null,
                            'target_not_applicable' => null,
                            'has_attachments' => null,
                            'verified' => null,
                            'verified_by' => null,
                            'date_verified' => null,
                            'created_by' => $userId,
                            'date_created' => $nowManila,
                            'modified_by' => $userId,
                            'date_modified' => $nowManila,
                        ]);
                    }
                }
            }
        });

        return back()->with('success', __('Annual targets have been saved and locked.'));
    }

    public function unlock(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $selectedYear = (int) $request->input('year', now()->year);

        // Check if rating record for selected year exists in ipc_semester
        $isExistingInIpcSemester = DB::table('ipc_semester')
            ->where('user_id', $userId)
            ->where('year', $selectedYear)
            ->exists();

        if ($isExistingInIpcSemester) {
            return back()->with('error', __('Cannot unlock targets. A rating record for the selected year already exists in My Ratings (ipc_semester). Please remove the rating record first.'));
        }

        DB::transaction(function () use ($userId, $selectedYear): void {
            $targetQuery = DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('target_status', 3)
                ->when($selectedYear > 0, fn($q) => $q->where('target_year', $selectedYear));

            $targetIds = (clone $targetQuery)->pluck('id');

            $targetQuery->update(['target_status' => 1]);

            if ($targetIds->isNotEmpty()) {
                DB::table('ipc_targets_indicators_itemlist')
                    ->whereIn('ind_id', $targetIds)
                    ->where('indi_status', 3)
                    ->update(['indi_status' => 1]);
            }
        });

        return back()->with('success', __('Annual targets have been unlocked.'));
    }

    public function getCopyData(Request $request): JsonResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $tab = $request->input('tab', 'staff');
        $staffUserId = $request->input('staffUserId');
        $harmonizedPositionId = $request->input('harmonizedPositionId');
        $sourceYear = $request->input('year', (string) now()->year);
        $category = $request->input('category', '');
        $semester = $request->input('semester', '');
        $statusFilter = $request->input('statusFilter', '');
        $search = $request->input('search', '');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;

        $staffUsers = DB::table('users')
            ->where('id', '!=', $userId)
            ->select('id', DB::raw("CONCAT(COALESCE(last_name, ''), ', ', COALESCE(first_name, '')) as name"), 'position')
            ->orderBy('last_name')
            ->get();

        $harmonizedPositions = DB::table('lib_harmonized_positions')
            ->where('is_active', 1)
            ->select('id', 'name')
            ->orderBy('sort_order')
            ->get();

        $existingActivities = DB::table('ipc_targets_indicators')
            ->where('user_id', $userId)
            ->where('target_year', $sourceYear)
            ->pluck('activity')
            ->map(fn ($act) => trim(mb_strtolower((string) $act)))
            ->filter()
            ->all();

        $items = [];
        $total = 0;

        if ($tab === 'staff' && filled($staffUserId)) {
            $query = DB::table('ipc_targets_indicators as iti')
                ->join('ipc_targets_indicators_itemlist as itl', 'itl.ind_id', '=', 'iti.id')
                ->where('iti.user_id', (int) $staffUserId)
                ->when(filled($sourceYear), fn ($q) => $q->where('iti.target_year', $sourceYear))
                ->when(filled($category), fn ($q) => $q->where('iti.kra_category', (int) $category))
                ->when(filled($semester), fn ($q) => $q->where('itl.new_semester', (int) $semester))
                ->when(filled($search), function ($q) use ($search): void {
                    $like = '%'.trim($search).'%';
                    $q->where(function ($sub) use ($like): void {
                        $sub->where('iti.activity', 'like', $like)
                            ->orWhere('itl.description', 'like', $like);
                    });
                });

            $rows = $query->select([
                'iti.id as ind_id',
                'iti.kra_category',
                'iti.activity',
                'iti.target_year',
                'itl.id as item_id',
                'itl.new_semester',
                'itl.description',
                'itl.rg_efficiency_',
                'itl.rg_quality_',
                'itl.rg_timeliness_',
                'itl.rg_mov_',
                'itl.rg_remarks_',
            ])
            ->orderBy('iti.kra_category')
            ->orderBy('iti.display_order')
            ->orderBy('itl.display_order')
            ->get();

            $grouped = $rows->groupBy('ind_id');
            $total = $grouped->count();
            $paginatedGroups = $grouped->forPage($page, $perPage);

            foreach ($paginatedGroups as $indId => $groupRows) {
                $first = $groupRows->first();
                $activityClean = trim(mb_strtolower((string) ($first->activity ?? '')));
                $isExisting = in_array($activityClean, $existingActivities, true);

                if ($statusFilter === 'new' && $isExisting) {
                    continue;
                }
                if ($statusFilter === 'existing' && ! $isExisting) {
                    continue;
                }

                $items[] = [
                    'indicatorId' => (int) $indId,
                    'kraCategory' => (int) $first->kra_category,
                    'activity' => (string) $first->activity,
                    'targetYear' => (int) $first->target_year,
                    'isExisting' => $isExisting,
                    'subTargets' => $groupRows->map(fn ($r) => [
                        'id' => (int) $r->item_id,
                        'newSemester' => (int) $r->new_semester,
                        'description' => (string) $r->description,
                        'efficiency' => (string) $r->rg_efficiency_,
                        'quality' => (string) $r->rg_quality_,
                        'timeliness' => (string) $r->rg_timeliness_,
                        'movs' => (string) $r->rg_mov_,
                        'remarks' => (string) $r->rg_remarks_,
                    ])->values()->all(),
                ];
            }
        } elseif ($tab === 'harmonized' && filled($harmonizedPositionId)) {
            $query = DB::table('harmonized_ipc_targets_indicators as iti')
                ->join('harmonized_ipc_targets_indicators_itemlist as itl', 'itl.ind_id', '=', 'iti.id')
                ->where('iti.harmonized_position_id', (int) $harmonizedPositionId)
                ->when(filled($sourceYear), fn ($q) => $q->where('iti.target_year', $sourceYear))
                ->when(filled($category), fn ($q) => $q->where('iti.kra_category', (int) $category))
                ->when(filled($semester), fn ($q) => $q->where('itl.new_semester', (int) $semester))
                ->when(filled($search), function ($q) use ($search): void {
                    $like = '%'.trim($search).'%';
                    $q->where(function ($sub) use ($like): void {
                        $sub->where('iti.activity', 'like', $like)
                            ->orWhere('itl.description', 'like', $like);
                    });
                });

            $rows = $query->select([
                'iti.id as ind_id',
                'iti.kra_category',
                'iti.activity',
                'iti.target_year',
                'itl.id as item_id',
                'itl.new_semester',
                'itl.description',
                'itl.rg_efficiency_',
                'itl.rg_quality_',
                'itl.rg_timeliness_',
                'itl.rg_mov_',
                'itl.rg_remarks_',
            ])
            ->orderBy('iti.kra_category')
            ->orderBy('iti.display_order')
            ->orderBy('itl.display_order')
            ->get();

            $grouped = $rows->groupBy('ind_id');
            $total = $grouped->count();
            $paginatedGroups = $grouped->forPage($page, $perPage);

            foreach ($paginatedGroups as $indId => $groupRows) {
                $first = $groupRows->first();
                $activityClean = trim(mb_strtolower((string) ($first->activity ?? '')));
                $isExisting = in_array($activityClean, $existingActivities, true);

                if ($statusFilter === 'new' && $isExisting) {
                    continue;
                }
                if ($statusFilter === 'existing' && ! $isExisting) {
                    continue;
                }

                $items[] = [
                    'indicatorId' => (int) $indId,
                    'kraCategory' => (int) $first->kra_category,
                    'activity' => (string) $first->activity,
                    'targetYear' => (int) $first->target_year,
                    'isExisting' => $isExisting,
                    'subTargets' => $groupRows->map(fn ($r) => [
                        'id' => (int) $r->item_id,
                        'newSemester' => (int) $r->new_semester,
                        'description' => (string) $r->description,
                        'efficiency' => (string) $r->rg_efficiency_,
                        'quality' => (string) $r->rg_quality_,
                        'timeliness' => (string) $r->rg_timeliness_,
                        'movs' => (string) $r->rg_mov_,
                        'remarks' => (string) $r->rg_remarks_,
                    ])->values()->all(),
                ];
            }
        }

        return response()->json([
            'staffUsers' => $staffUsers,
            'harmonizedPositions' => $harmonizedPositions,
            'existingActivities' => $existingActivities,
            'copyTargets' => [
                'data' => $items,
                'total' => $total,
                'currentPage' => $page,
                'lastPage' => max(1, (int) ceil($total / $perPage)),
            ],
        ]);
    }

    public function copyStaffTargetGroup(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $indicatorId = (int) $request->input('indicatorId');
        $targetYear = (int) $request->input('targetYear', now()->year);

        $sourceIndicator = DB::table('ipc_targets_indicators')->where('id', $indicatorId)->first();
        if (! $sourceIndicator) {
            return back()->with('error', __('Source indicator not found.'));
        }

        $sourceItems = DB::table('ipc_targets_indicators_itemlist')->where('ind_id', $indicatorId)->get();

        DB::transaction(function () use ($sourceIndicator, $sourceItems, $userId, $targetYear): void {
            $maxOrder = (int) DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('kra_category', $sourceIndicator->kra_category)
                ->max('display_order');

            $newIndId = DB::table('ipc_targets_indicators')->insertGetId([
                'user_id' => $userId,
                'target_sem' => $sourceIndicator->target_sem ?? 1,
                'target_year' => $targetYear,
                'kra_category' => $sourceIndicator->kra_category,
                'display_order' => $maxOrder + 1,
                'activity' => $sourceIndicator->activity,
                'remarks' => $sourceIndicator->remarks ?? '',
                'target_status' => 1,
                'date_created' => now(),
            ]);

            foreach ($sourceItems as $item) {
                DB::table('ipc_targets_indicators_itemlist')->insert([
                    'ind_id' => $newIndId,
                    'display_order' => $item->display_order,
                    'new_semester' => $item->new_semester,
                    'description' => $item->description,
                    'rg_efficiency_' => $item->rg_efficiency_,
                    'rg_quality_' => $item->rg_quality_,
                    'rg_timeliness_' => $item->rg_timeliness_,
                    'rg_mov_' => $item->rg_mov_,
                    'rg_remarks_' => $item->rg_remarks_,
                    'indi_status' => 1,
                    'date_created' => now(),
                ]);
            }
        });

        return back()->with('success', __('Target copied successfully.'));
    }

    public function copyHarmonizedTargetGroup(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $indicatorId = (int) $request->input('indicatorId');
        $targetYear = (int) $request->input('targetYear', now()->year);

        $sourceIndicator = DB::table('harmonized_ipc_targets_indicators')->where('id', $indicatorId)->first();
        if (! $sourceIndicator) {
            return back()->with('error', __('Source harmonized indicator not found.'));
        }

        $sourceItems = DB::table('harmonized_ipc_targets_indicators_itemlist')->where('ind_id', $indicatorId)->get();

        DB::transaction(function () use ($sourceIndicator, $sourceItems, $userId, $targetYear): void {
            $maxOrder = (int) DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('kra_category', $sourceIndicator->kra_category)
                ->max('display_order');

            $newIndId = DB::table('ipc_targets_indicators')->insertGetId([
                'user_id' => $userId,
                'target_sem' => $sourceIndicator->target_sem ?? 1,
                'target_year' => $targetYear,
                'kra_category' => $sourceIndicator->kra_category,
                'display_order' => $maxOrder + 1,
                'activity' => $sourceIndicator->activity,
                'remarks' => '',
                'target_status' => 1,
                'date_created' => now(),
            ]);

            foreach ($sourceItems as $item) {
                DB::table('ipc_targets_indicators_itemlist')->insert([
                    'ind_id' => $newIndId,
                    'display_order' => $item->display_order,
                    'new_semester' => $item->new_semester,
                    'description' => $item->description,
                    'rg_efficiency_' => $item->rg_efficiency_,
                    'rg_quality_' => $item->rg_quality_,
                    'rg_timeliness_' => $item->rg_timeliness_,
                    'rg_mov_' => $item->rg_mov_,
                    'rg_remarks_' => $item->rg_remarks_,
                    'indi_status' => 1,
                    'date_created' => now(),
                ]);
            }
        });

        return back()->with('success', __('Harmonized target copied successfully.'));
    }

    private function validatedMove(int $userId, array $source, array $target): ?array
    {
        $sourceType = (string) ($source['type'] ?? '');
        $targetType = (string) ($target['type'] ?? '');
        $sourceIndicatorId = (int) ($source['indicatorId'] ?? 0);
        $targetIndicatorId = (int) ($target['indicatorId'] ?? 0);

        if (! in_array($sourceType, ['main', 'sub'], true) || ! in_array($targetType, ['main', 'sub', 'category'], true)) {
            return null;
        }

        $includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);
        $allowedKra = $includeStrategicFunction ? [1, 2, 3] : [2, 3];

        $sourceIndicator = DB::table('ipc_targets_indicators')
            ->where('id', $sourceIndicatorId)
            ->where('user_id', $userId)
            ->first();

        $targetIndicator = $targetIndicatorId > 0
            ? DB::table('ipc_targets_indicators')->where('id', $targetIndicatorId)->where('user_id', $userId)->first()
            : null;

        $targetKra = $targetType === 'category' ? (int) ($target['kra'] ?? 0) : (int) ($targetIndicator->kra_category ?? 0);

        if ($sourceIndicator === null || ! in_array($targetKra, $allowedKra, true) || ($targetType !== 'category' && $targetIndicator === null)) {
            return null;
        }

        return [
            'userId' => $userId,
            'sourceType' => $sourceType,
            'targetType' => $targetType,
            'sourceIndicator' => $sourceIndicator,
            'targetIndicator' => $targetIndicator,
            'targetKra' => $targetKra,
            'sourceItemId' => (int) ($source['itemId'] ?? 0),
            'targetItemId' => (int) ($target['itemId'] ?? 0),
        ];
    }

    private function applyMove(array $move): void
    {
        DB::transaction(function () use ($move): void {
            $userId = (int) $move['userId'];
            $sourceType = (string) $move['sourceType'];
            $targetType = (string) $move['targetType'];
            $sourceIndicator = $move['sourceIndicator'];
            $targetIndicator = $move['targetIndicator'];
            $targetKra = (int) $move['targetKra'];

            if ($sourceType === 'main') {
                if ($targetType === 'category') {
                    $maxOrder = (int) DB::table('ipc_targets_indicators')
                        ->where('user_id', $userId)
                        ->where('kra_category', $targetKra)
                        ->max('display_order');

                    DB::table('ipc_targets_indicators')
                        ->where('id', $sourceIndicator->id)
                        ->where('user_id', $userId)
                        ->update([
                            'kra_category' => $targetKra,
                            'display_order' => $maxOrder + 1,
                        ]);
                } elseif ($targetIndicator !== null) {
                    $sourceOrder = (int) $sourceIndicator->display_order;
                    $targetOrder = (int) $targetIndicator->display_order;

                    DB::table('ipc_targets_indicators')
                        ->where('id', $sourceIndicator->id)
                        ->where('user_id', $userId)
                        ->update([
                            'kra_category' => $targetKra,
                            'display_order' => $targetOrder,
                        ]);

                    DB::table('ipc_targets_indicators')
                        ->where('id', $targetIndicator->id)
                        ->where('user_id', $userId)
                        ->update([
                            'display_order' => $sourceOrder,
                        ]);
                }
            }
        });
    }
}
