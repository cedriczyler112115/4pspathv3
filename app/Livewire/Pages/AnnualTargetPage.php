<?php

namespace App\Livewire\Pages;

use App\Actions\AnnualTargets\DeleteAnnualTarget;
use App\Actions\AnnualTargets\CreateAnnualTarget;
use App\Actions\AnnualTargets\UpdateAnnualTarget;
use App\Models\ApplicationSetting;
use App\Services\AnnualTargetDirectory;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use stdClass;

#[Title('Annual Target')]
class AnnualTargetPage extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public string $search = '';

    public string $yearFilter = '';

    public string $categoryFilter = '';

    public string $semesterFilter = '';

    public string $fullName = '';

    public string $position = '';

    public string $designation = '';

    public string $divisionName = '';

    public string $sectionName = '';

    public bool $includeStrategicFunction = true;

    public bool $showOnlyDuplicates = false;

    public ?int $editingRowId = null;

    public ?int $editingIndicatorId = null;

    public bool $showDeleteModal = false;

    public bool $showDeleteSubTargetModal = false;

    public bool $showLockModal = false;

    public bool $showUnlockModal = false;

    public bool $showCopyModal = false;

    public bool $showCopyAllConfirmModal = false;

    public string $copyAllTargetType = 'staff';

    public string $copyTab = 'staff';

    public string $copyStaffUserId = '';

    public string $copyStaffYear = '';

    public string $copyStaffSearch = '';

    public string $copyHarmonizedPositionId = '';

    public string $copyHarmonizedYear = '';

    public string $copyHarmonizedSearch = '';

    public bool $showAddModal = false;

    public bool $showMoveConfirmModal = false;

    #[Locked]
    public ?int $pendingMoveIndicatorId = null;

    #[Locked]
    public ?int $pendingMoveTargetIndicatorId = null;

    #[Locked]
    public ?int $pendingMoveTargetKra = null;

    public ?int $deletingRowId = null;

    public ?int $deletingIndicatorId = null;

    public ?int $deletingSubTargetItemId = null;

    public ?int $addingKraCategory = null;

    public ?int $addingYear = null;

    public string $addActivity = '';

    public string $addSemester = '';

    public string $addDescription = '';

    public string $addEfficiency = '';

    public string $addQuality = '';

    public string $addTimeliness = '';

    public string $addMovs = '';

    public string $addRemarks = '';

    public string $editActivity = '';

    public string $editCategory = '';

    /** @var array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> */
    public array $editRows = [];

    public function mount(): void
    {
        $this->includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);
        $this->perPage = (int) Session::get($this->sessionKey('perPage'), 10);
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->yearFilter = (string) Session::get($this->sessionKey('yearFilter'), now()->year);
        $this->categoryFilter = (string) Session::get($this->sessionKey('categoryFilter'), '');
        $this->semesterFilter = (string) Session::get($this->sessionKey('semesterFilter'), '');
        $this->showOnlyDuplicates = (bool) Session::get($this->sessionKey('showOnlyDuplicates'), false);

        if (!$this->includeStrategicFunction && $this->categoryFilter === '1') {
            $this->categoryFilter = '';
            Session::forget($this->sessionKey('categoryFilter'));
        }

        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $user = DB::table('users')
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

        if ($user === null) {
            return;
        }

        $this->fullName = trim(($user->last_name ?? '') . (filled($user->last_name) ? ', ' : '') . collect([$user->first_name, $user->middle_name])->filter()->join(' '));
        $this->position = (string) ($user->position ?? '');
        $this->designation = (string) ($user->designation ?? '');
        $this->divisionName = (string) ($user->division_name ?? '');
        $this->sectionName = (string) ($user->section_name ?? '');
    }

    public function render(): View
    {
        $categories = $this->categories();

        return view('livewire.pages.annual-target-page', [
            'annualTargets' => $this->annualTargets(),
            'years' => $this->years(),
            'categories' => $categories,
            'visibleCategories' => $this->categoryFilter === ''
                ? $categories
                : $categories->where('value', $this->categoryFilter)->values(),
            'semesters' => $this->semesters(),
            'perPageOptions' => $this->perPageOptions(),
        ]);
    }

    public function updatedPerPage(): void
    {
        if (!in_array($this->perPage, [10, 20, 50], true)) {
            $this->perPage = 10;
        }

        Session::put($this->sessionKey('perPage'), $this->perPage);
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        Session::put($this->sessionKey('search'), $this->search);
        $this->resetPage();
    }

    public function updatedYearFilter(): void
    {
        Session::put($this->sessionKey('yearFilter'), $this->yearFilter);
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        if ($this->categoryFilter !== '' && !in_array((int) $this->categoryFilter, $this->allowedKraCategories(), true)) {
            $this->categoryFilter = '';
        }

        Session::put($this->sessionKey('categoryFilter'), $this->categoryFilter);
        $this->resetPage();
    }

    public function updatedSemesterFilter(): void
    {
        Session::put($this->sessionKey('semesterFilter'), $this->semesterFilter);
        $this->resetPage();
    }

    public function updatedShowOnlyDuplicates(): void
    {
        Session::put($this->sessionKey('showOnlyDuplicates'), $this->showOnlyDuplicates);
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->yearFilter = (string) now()->year;
        $this->categoryFilter = '';
        $this->semesterFilter = '';
        $this->showOnlyDuplicates = false;

        Session::forget([
            $this->sessionKey('search'),
            $this->sessionKey('yearFilter'),
            $this->sessionKey('categoryFilter'),
            $this->sessionKey('semesterFilter'),
            $this->sessionKey('showOnlyDuplicates'),
        ]);

        $this->resetPage();
    }

    public function editRow(int $rowId): void
    {
        $row = DB::table('ipc_targets_indicators_itemlist as itl')
            ->leftJoin('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
            ->where('itl.id', $rowId)
            ->where('iti.user_id', Auth::id())
            ->select([
                'itl.id',
                'itl.ind_id',
                'iti.kra_category',
                'iti.target_sem',
                'iti.activity',
                'itl.new_semester',
                'itl.description',
                'itl.rg_efficiency_',
                'itl.rg_quality_',
                'itl.rg_timeliness_',
                'itl.rg_mov_',
                'itl.rg_remarks_',
            ])
            ->first();

        if ($row === null) {
            return;
        }

        $this->editingRowId = (int) $row->id;
        $this->editingIndicatorId = (int) $row->ind_id;
        $this->editActivity = (string) ($row->activity ?? '');
        $this->editCategory = (string) ($row->kra_category ?? '');
        $this->editRows = DB::table('ipc_targets_indicators_itemlist')
            ->where('ind_id', $this->editingIndicatorId)
            ->orderBy('date_created', 'asc')
            ->get([
                'id',
                'new_semester',
                'description',
                'rg_efficiency_',
                'rg_quality_',
                'rg_timeliness_',
                'rg_mov_',
                'rg_remarks_',
            ])
            ->mapWithKeys(fn(object $item): array => [
                (int) $item->id => [
                    'semester' => $this->normalizeSemesterValue($item->new_semester ?? null),
                    'description' => $this->normalizeTextareaValue($item->description ?? ''),
                    'efficiency' => $this->normalizeTextareaValue($item->rg_efficiency_ ?? ''),
                    'quality' => $this->normalizeTextareaValue($item->rg_quality_ ?? ''),
                    'timeliness' => $this->normalizeTextareaValue($item->rg_timeliness_ ?? ''),
                    'movs' => $this->normalizeTextareaValue($item->rg_mov_ ?? ''),
                    'remarks' => $this->normalizeTextareaValue($item->rg_remarks_ ?? ''),
                ],
            ])
            ->all();
    }

    public function cancelEdit(): void
    {
        $this->editingRowId = null;
        $this->editingIndicatorId = null;
        $this->editActivity = '';
        $this->editCategory = '';
        $this->editRows = [];
    }

    #[On('open-add-target-modal')]
    public function openAddModal(int $kraCategory): void
    {
        $userId = Auth::id();

        if ($userId === null || !in_array($kraCategory, $this->allowedKraCategories(), true)) {
            return;
        }

        $this->cancelEdit();
        $this->showAddModal = true;
        $this->addingKraCategory = $kraCategory;
        $this->addingYear = ctype_digit($this->yearFilter) ? (int) $this->yearFilter : now()->year;
        $this->addActivity = '';
        $this->addSemester = ctype_digit($this->semesterFilter) ? (string) $this->semesterFilter : '1';
        $this->addDescription = '';
        $this->addEfficiency = '';
        $this->addQuality = '';
        $this->addTimeliness = '';
        $this->addMovs = '';
        $this->addRemarks = '';
    }

    public function cancelAdd(): void
    {
        $this->showAddModal = false;
        $this->addingKraCategory = null;
        $this->addingYear = null;
        $this->addActivity = '';
        $this->addSemester = '';
        $this->addDescription = '';
        $this->addEfficiency = '';
        $this->addQuality = '';
        $this->addTimeliness = '';
        $this->addMovs = '';
        $this->addRemarks = '';
    }

    public function saveAdd(): void
    {
        $userId = Auth::id();

        if ($userId === null || $this->addingKraCategory === null || !in_array($this->addingKraCategory, $this->allowedKraCategories(), true)) {
            return;
        }

        validator([
            'addActivity' => $this->addActivity,
            'addSemester' => $this->addSemester,
            'addDescription' => $this->addDescription,
            'addEfficiency' => $this->addEfficiency,
            'addQuality' => $this->addQuality,
            'addTimeliness' => $this->addTimeliness,
            'addMovs' => $this->addMovs,
            'addRemarks' => $this->addRemarks,
        ], [
            'addActivity' => ['required', 'string'],
            'addSemester' => ['required', 'regex:/^\d+$/'],
            'addDescription' => ['required', 'string'],
            'addEfficiency' => ['required', 'string'],
            'addQuality' => ['required', 'string'],
            'addTimeliness' => ['required', 'string'],
            'addMovs' => ['required', 'string'],
            'addRemarks' => ['nullable', 'string'],
        ])->validate();

        $year = $this->addingYear ?? now()->year;
        app(CreateAnnualTarget::class)->execute($userId, $year, $this->addingKraCategory, [
            'activity' => $this->addActivity,
            'semester' => $this->addSemester,
            'description' => $this->addDescription,
            'efficiency' => $this->addEfficiency,
            'quality' => $this->addQuality,
            'timeliness' => $this->addTimeliness,
            'movs' => $this->addMovs,
            'remarks' => $this->addRemarks,
        ]);

        $this->cancelAdd();
        Flux::toast(variant: 'success', text: __('Annual target added.'));
    }

    public function saveEdit(): void
    {
        if ($this->editingRowId === null) {
            return;
        }

        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $rules = $this->buildSaveRules();
        $validated = validator([
            'editActivity' => $this->editActivity,
            'editCategory' => $this->editCategory,
            'editRows' => $this->editRows,
        ], $rules)->validate();

        $this->editActivity = (string) ($validated['editActivity'] ?? '');
        $this->editCategory = (string) ($validated['editCategory'] ?? '');
        $this->editRows = (array) ($validated['editRows'] ?? []);

        DB::transaction(function () use ($userId): void {
            $row = DB::table('ipc_targets_indicators_itemlist as itl')
                ->leftJoin('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                ->where('itl.id', $this->editingRowId)
                ->where('iti.user_id', $userId)
                ->select(['itl.id'])
                ->first();

            if ($row === null) {
                return;
            }

            foreach ($this->editRows as $itemId => $rowValues) {
                DB::table('ipc_targets_indicators_itemlist')
                    ->where('id', (int) $itemId)
                    ->where('ind_id', $this->editingIndicatorId)
                    ->update([
                        'new_semester' => $this->semesterToDatabaseValue($rowValues['semester'] ?? null),
                        'description' => $rowValues['description'] ?? '',
                        'rg_efficiency_' => $rowValues['efficiency'] ?? '',
                        'rg_quality_' => $rowValues['quality'] ?? '',
                        'rg_timeliness_' => $rowValues['timeliness'] ?? '',
                        'rg_mov_' => $rowValues['movs'] ?? '',
                        'rg_remarks_' => $rowValues['remarks'] ?? '',
                    ]);
            }

            DB::table('ipc_targets_indicators')
                ->where('id', $this->editingIndicatorId)
                ->where('user_id', $userId)
                ->update([
                    'activity' => $this->editActivity,
                    'kra_category' => $this->editCategory,
                ]);
        });

        $this->cancelEdit();
        Flux::toast(variant: 'success', text: __('Annual target updated.'));
    }

    #[On('annual-target-delete-requested')]
    public function deleteRow(int $rowId): void
    {
        $row = DB::table('ipc_targets_indicators_itemlist as itl')
            ->leftJoin('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
            ->where('itl.id', $rowId)
            ->where('iti.user_id', Auth::id())
            ->select(['itl.ind_id'])
            ->first();

        if ($row === null) {
            return;
        }

        $this->deletingRowId = $rowId;
        $this->deletingIndicatorId = (int) $row->ind_id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingRowId = null;
        $this->deletingIndicatorId = null;
    }

    public function confirmDelete(): void
    {
        $userId = Auth::id();

        if ($this->deletingIndicatorId === null || !is_int($userId)) {
            return;
        }

        $indicatorId = $this->deletingIndicatorId;

        if (!app(DeleteAnnualTarget::class)->execute($indicatorId, $userId)) {
            $this->cancelDelete();

            return;
        }

        if ($this->editingIndicatorId === $indicatorId) {
            $this->cancelEdit();
        }

        $this->cancelDelete();

        Flux::toast(variant: 'success', text: __('Annual target and its sub-targets deleted.'));
    }

    #[On('annual-target-subtarget-delete-requested')]
    public function deleteSubTarget(int $itemId): void
    {
        $this->deletingSubTargetItemId = $itemId;
        $this->showDeleteSubTargetModal = true;
    }

    public function cancelDeleteSubTarget(): void
    {
        $this->showDeleteSubTargetModal = false;
        $this->deletingSubTargetItemId = null;
    }

    public function confirmDeleteSubTarget(): void
    {
        $userId = Auth::id();
        if (!is_int($userId) || $this->deletingSubTargetItemId === null) {
            return;
        }

        $itemId = $this->deletingSubTargetItemId;

        if (app(DeleteAnnualTarget::class)->executeItem($itemId, $userId)) {
            $this->cancelDeleteSubTarget();
            Flux::toast(variant: 'success', text: __('Sub-target deleted.'));
        } else {
            $this->cancelDeleteSubTarget();
        }
    }

    public function requestLockAnnualTarget(): void
    {
        $this->showLockModal = true;
    }

    public function cancelLockAnnualTarget(): void
    {
        $this->showLockModal = false;
    }

    public function confirmLockAnnualTarget(): void
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            $this->cancelLockAnnualTarget();

            return;
        }

        DB::transaction(function () use ($userId): void {
            DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('target_status', 1)
                ->update(['target_status' => 3]);

            DB::table('ipc_targets_indicators_itemlist as itl')
                ->join('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                ->where('iti.user_id', $userId)
                ->where('itl.indi_status', 1)
                ->update(['itl.indi_status' => 3]);
        });

        $this->cancelLockAnnualTarget();

        Flux::toast(variant: 'success', text: __('Annual targets have been saved and locked.'));
    }

    public function isLocked(): bool
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return false;
        }

        $activeCount = DB::table('ipc_targets_indicators')
            ->where('user_id', $userId)
            ->where('target_status', 1)
            ->count();

        $lockedCount = DB::table('ipc_targets_indicators')
            ->where('user_id', $userId)
            ->where('target_status', 3)
            ->count();

        return $lockedCount > 0 && $activeCount === 0;
    }

    public function requestUnlockAnnualTarget(): void
    {
        $this->showUnlockModal = true;
    }

    public function cancelUnlockAnnualTarget(): void
    {
        $this->showUnlockModal = false;
    }

    public function confirmUnlockAnnualTarget(): void
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            $this->cancelUnlockAnnualTarget();

            return;
        }

        DB::transaction(function () use ($userId): void {
            DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('target_status', 3)
                ->update(['target_status' => 1]);

            DB::table('ipc_targets_indicators_itemlist as itl')
                ->join('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                ->where('iti.user_id', $userId)
                ->where('itl.indi_status', 3)
                ->update(['itl.indi_status' => 1]);
        });

        $this->cancelUnlockAnnualTarget();

        Flux::toast(variant: 'success', text: __('Annual targets have been unlocked.'));
    }

    public function openCopyModal(): void
    {
        $this->showCopyModal = true;
        if ($this->copyStaffYear === '') {
            $this->copyStaffYear = (string) now()->year;
        }
        if ($this->copyHarmonizedYear === '') {
            $this->copyHarmonizedYear = (string) now()->year;
        }
    }

    public function closeCopyModal(): void
    {
        $this->showCopyModal = false;
    }

    public function copyStaffUsers(): Collection
    {
        return DB::table('ipc_targets_indicators as iti')
            ->join('users as u', 'iti.user_id', '=', 'u.id')
            ->where('iti.user_id', '!=', Auth::id())
            ->select(['u.id', 'u.first_name', 'u.middle_name', 'u.last_name', 'u.position'])
            ->distinct()
            ->orderBy('u.last_name')
            ->orderBy('u.first_name')
            ->get()
            ->map(function (object $u): object {
                $u->full_name = mb_strtoupper(trim(($u->last_name ?? '') . (filled($u->last_name) ? ', ' : '') . collect([$u->first_name, $u->middle_name])->filter()->join(' ')), 'UTF-8');

                return $u;
            });
    }

    public function copyStaffYears(): Collection
    {
        return $this->years();
    }

    public function copyHarmonizedPositions(): Collection
    {
        if (DB::getSchemaBuilder()->hasTable('lib_harmonized_positions')) {
            $positions = DB::table('lib_harmonized_positions')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
            if ($positions->isNotEmpty()) {
                return $positions;
            }
        }

        return DB::table('harmonized_ipc_targets_indicators')
            ->whereNotNull('harmonized_position_id')
            ->distinct()
            ->pluck('harmonized_position_id')
            ->map(fn($posId): object => (object) ['id' => $posId, 'name' => 'Position #' . $posId]);
    }

    public function copyHarmonizedYears(): Collection
    {
        return $this->years();
    }

    /** @return array<int, string> */
    public function getExistingActivitiesProperty(): array
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return [];
        }

        $targetYear = ctype_digit($this->yearFilter) ? (int) $this->yearFilter : now()->year;

        return DB::table('ipc_targets_indicators')
            ->where('user_id', $userId)
            ->where('target_year', $targetYear)
            ->pluck('activity')
            ->map(fn($act) => trim(mb_strtolower((string) $act)))
            ->filter()
            ->all();
    }

    public string $copyStaffCategory = '';

    public string $copyStaffSemester = '';

    public string $copyHarmonizedCategory = '';

    public string $copyHarmonizedSemester = '';

    public string $copyStaffStatusFilter = '';

    public string $copyHarmonizedStatusFilter = '';

    public function copyStaffTargetGroups(): Collection
    {
        if ($this->copyStaffUserId === '') {
            return collect();
        }

        $query = DB::table('ipc_targets_indicators as iti')
            ->join('ipc_targets_indicators_itemlist as itl', 'itl.ind_id', '=', 'iti.id')
            ->where('iti.user_id', (int) $this->copyStaffUserId);

        if ($this->copyStaffYear !== '') {
            $query->where('iti.target_year', $this->copyStaffYear);
        }

        if ($this->copyStaffCategory !== '') {
            $query->where('iti.kra_category', (int) $this->copyStaffCategory);
        }

        if ($this->copyStaffSemester !== '') {
            $query->where('itl.new_semester', (int) $this->copyStaffSemester);
        }

        if (trim($this->copyStaffSearch) !== '') {
            $search = '%' . trim($this->copyStaffSearch) . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('iti.activity', 'like', $search)
                    ->orWhere('itl.description', 'like', $search);
            });
        }

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
            'itl.rg_ratingperiod_',
            'itl.rg_mov_',
            'itl.rg_remarks_',
        ])
            ->orderBy('iti.kra_category')
            ->orderBy('iti.display_order')
            ->orderBy('itl.display_order')
            ->get();

        return $rows->groupBy('ind_id');
    }

    public function copyHarmonizedTargetGroups(): Collection
    {
        if ($this->copyHarmonizedPositionId === '') {
            return collect();
        }

        $query = DB::table('harmonized_ipc_targets_indicators as iti')
            ->join('harmonized_ipc_targets_indicators_itemlist as itl', 'itl.ind_id', '=', 'iti.id')
            ->where('iti.harmonized_position_id', (int) $this->copyHarmonizedPositionId);

        if ($this->copyHarmonizedYear !== '') {
            $query->where('iti.target_year', $this->copyHarmonizedYear);
        }

        if ($this->copyHarmonizedCategory !== '') {
            $query->where('iti.kra_category', (int) $this->copyHarmonizedCategory);
        }

        if ($this->copyHarmonizedSemester !== '') {
            $query->where('itl.new_semester', (int) $this->copyHarmonizedSemester);
        }

        if (trim($this->copyHarmonizedSearch) !== '') {
            $search = '%' . trim($this->copyHarmonizedSearch) . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('iti.activity', 'like', $search)
                    ->orWhere('itl.description', 'like', $search);
            });
        }

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
            'itl.rg_ratingperiod_',
            'itl.rg_mov_',
            'itl.rg_remarks_',
        ])
            ->orderBy('iti.kra_category')
            ->orderBy('iti.display_order')
            ->orderBy('itl.display_order')
            ->get();

        return $rows->groupBy('ind_id');
    }

    public function copyAllStaffTargetGroups(): void
    {
        $groups = $this->copyStaffTargetGroups();
        if ($groups->isEmpty()) {
            return;
        }

        $existingActivities = $this->existingActivities;
        $copiedCount = 0;

        foreach ($groups as $indicatorId => $items) {
            $first = $items->first();
            if ($first === null) {
                continue;
            }

            $activityClean = trim(mb_strtolower((string) ($first->activity ?? '')));
            if ($activityClean !== '' && in_array($activityClean, $existingActivities, true)) {
                continue;
            }

            $this->copyStaffTargetGroup((int) $indicatorId);
            $existingActivities[] = $activityClean;
            $copiedCount++;
        }

        if ($copiedCount > 0) {
            Flux::toast(variant: 'success', text: __(':count target group(s) copied successfully.', ['count' => $copiedCount]));
        } else {
            Flux::toast(variant: 'warning', text: __('No new targets available to copy (all matching results already exist).'));
        }
    }

    public function copyAllHarmonizedTargetGroups(): void
    {
        $groups = $this->copyHarmonizedTargetGroups();
        if ($groups->isEmpty()) {
            return;
        }

        $existingActivities = $this->existingActivities;
        $copiedCount = 0;

        foreach ($groups as $indicatorId => $items) {
            $first = $items->first();
            if ($first === null) {
                continue;
            }

            $activityClean = trim(mb_strtolower((string) ($first->activity ?? '')));
            if ($activityClean !== '' && in_array($activityClean, $existingActivities, true)) {
                continue;
            }

            $this->copyHarmonizedTargetGroup((int) $indicatorId);
            $existingActivities[] = $activityClean;
            $copiedCount++;
        }

        if ($copiedCount > 0) {
            Flux::toast(variant: 'success', text: __(':count harmonized target group(s) copied successfully.', ['count' => $copiedCount]));
        } else {
            Flux::toast(variant: 'warning', text: __('No new harmonized targets available to copy (all matching results already exist).'));
        }
    }

    public function requestCopyAllStaff(): void
    {
        $this->copyAllTargetType = 'staff';
        $this->showCopyAllConfirmModal = true;
    }

    public function requestCopyAllHarmonized(): void
    {
        $this->copyAllTargetType = 'harmonized';
        $this->showCopyAllConfirmModal = true;
    }

    public function cancelCopyAllConfirm(): void
    {
        $this->showCopyAllConfirmModal = false;
    }

    public function confirmCopyAll(): void
    {
        $this->showCopyAllConfirmModal = false;

        if ($this->copyAllTargetType === 'staff') {
            $this->copyAllStaffTargetGroups();
        } else {
            $this->copyAllHarmonizedTargetGroups();
        }
    }

    public function copyStaffTargetGroup(int $indicatorId): void
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return;
        }

        $sourceIndicator = DB::table('ipc_targets_indicators')->where('id', $indicatorId)->first();
        if ($sourceIndicator === null) {
            return;
        }

        $sourceItems = DB::table('ipc_targets_indicators_itemlist')->where('ind_id', $indicatorId)->get();

        DB::transaction(function () use ($sourceIndicator, $sourceItems, $userId): void {
            $targetYear = ctype_digit($this->yearFilter) ? (string) $this->yearFilter : (string) now()->year;

            $maxOrder = (int) DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('kra_category', $sourceIndicator->kra_category)
                ->max('display_order');

            $newIndId = DB::table('ipc_targets_indicators')->insertGetId([
                'user_id' => $userId,
                'target_group_id' => $sourceIndicator->target_group_id,
                'target_year' => $targetYear,
                'target_sem' => $sourceIndicator->target_sem,
                'kra_category' => $sourceIndicator->kra_category,
                'display_order' => $maxOrder + 1,
                'activity' => $sourceIndicator->activity,
                'remarks' => $sourceIndicator->remarks,
                'target_status' => 1,
                'created_by' => $userId,
                'date_created' => now(),
            ]);

            foreach ($sourceItems as $item) {
                DB::table('ipc_targets_indicators_itemlist')->insert([
                    'ind_id' => $newIndId,
                    'display_order' => $item->display_order,
                    'new_semester' => $item->new_semester,
                    'description' => $item->description,
                    'weight' => $item->weight,
                    'quantity' => $item->quantity,
                    'quality' => $item->quality,
                    'timeliness' => $item->timeliness,
                    'remarks' => $item->remarks,
                    'indi_status' => 1,
                    'created_by' => $userId,
                    'modified_by' => $userId,
                    'date_created' => now(),
                    'rg_efficiency_' => $item->rg_efficiency_,
                    'rg_quality_' => $item->rg_quality_,
                    'rg_timeliness_' => $item->rg_timeliness_,
                    'rg_ratingperiod_' => $item->rg_ratingperiod_,
                    'rg_mov_' => $item->rg_mov_,
                    'rg_remarks_' => $item->rg_remarks_,
                ]);
            }
        });

        Flux::toast(variant: 'success', text: __('Target copied successfully.'));
    }

    public function copyHarmonizedTargetGroup(int $indicatorId): void
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return;
        }

        $sourceIndicator = DB::table('harmonized_ipc_targets_indicators')->where('id', $indicatorId)->first();
        if ($sourceIndicator === null) {
            return;
        }

        $sourceItems = DB::table('harmonized_ipc_targets_indicators_itemlist')->where('ind_id', $indicatorId)->get();

        DB::transaction(function () use ($sourceIndicator, $sourceItems, $userId): void {
            $targetYear = ctype_digit($this->yearFilter) ? (string) $this->yearFilter : (string) now()->year;

            $maxOrder = (int) DB::table('ipc_targets_indicators')
                ->where('user_id', $userId)
                ->where('kra_category', $sourceIndicator->kra_category)
                ->max('display_order');

            $newIndId = DB::table('ipc_targets_indicators')->insertGetId([
                'user_id' => $userId,
                'target_group_id' => $sourceIndicator->target_group_id,
                'target_year' => $targetYear,
                'target_sem' => $sourceIndicator->target_sem,
                'kra_category' => $sourceIndicator->kra_category,
                'display_order' => $maxOrder + 1,
                'activity' => $sourceIndicator->activity,
                'remarks' => $sourceIndicator->remarks,
                'target_status' => 1,
                'created_by' => $userId,
                'date_created' => now(),
            ]);

            foreach ($sourceItems as $item) {
                DB::table('ipc_targets_indicators_itemlist')->insert([
                    'ind_id' => $newIndId,
                    'display_order' => $item->display_order,
                    'new_semester' => $item->new_semester,
                    'description' => $item->description,
                    'weight' => $item->weight,
                    'quantity' => $item->quantity,
                    'quality' => $item->quality,
                    'timeliness' => $item->timeliness,
                    'remarks' => $item->remarks,
                    'indi_status' => 1,
                    'created_by' => $userId,
                    'modified_by' => $userId,
                    'date_created' => now(),
                    'rg_efficiency_' => $item->rg_efficiency_,
                    'rg_quality_' => $item->rg_quality_,
                    'rg_timeliness_' => $item->rg_timeliness_,
                    'rg_ratingperiod_' => $item->rg_ratingperiod_,
                    'rg_mov_' => $item->rg_mov_,
                    'rg_remarks_' => $item->rg_remarks_,
                ]);
            }
        });

        Flux::toast(variant: 'success', text: __('Harmonized target copied successfully.'));
    }

    #[On('annual-target-target-dropped')]
    public function targetDropped(array $source, array $target): void
    {
        $move = $this->validatedMove($source, $target);

        if ($move === null) {
            return;
        }

        if ((int) $move['sourceKra'] !== (int) $move['targetKra']) {
            $this->pendingMoveIndicatorId = (int) $move['sourceIndicatorId'];
            $this->pendingMoveTargetIndicatorId = (int) $move['targetIndicatorId'] ?: null;
            $this->pendingMoveTargetKra = (int) $move['targetKra'];
            $this->showMoveConfirmModal = true;

            return;
        }

        $this->applyMove($move);
    }

    public function cancelTargetMove(): void
    {
        $this->showMoveConfirmModal = false;
        $this->pendingMoveIndicatorId = null;
        $this->pendingMoveTargetIndicatorId = null;
        $this->pendingMoveTargetKra = null;
    }

    public function confirmTargetMove(): void
    {
        if ($this->pendingMoveIndicatorId === null || $this->pendingMoveTargetKra === null) {
            return;
        }

        $sourceIndicatorId = $this->pendingMoveIndicatorId;
        $targetIndicatorId = $this->pendingMoveTargetIndicatorId;
        $targetKra = $this->pendingMoveTargetKra;
        $this->cancelTargetMove();

        $source = [
            'type' => 'main',
            'indicatorId' => $sourceIndicatorId,
            'itemId' => 0,
        ];
        $target = $targetIndicatorId === null
            ? ['type' => 'category', 'indicatorId' => 0, 'itemId' => 0, 'kra' => $targetKra]
            : ['type' => 'main', 'indicatorId' => $targetIndicatorId, 'itemId' => 0, 'kra' => $targetKra];
        $move = $this->validatedMove($source, $target);

        if ($move === null || (int) $move['sourceKra'] === (int) $move['targetKra']) {
            Flux::toast(variant: 'danger', text: __('Unable to move the target. Please try again.'));

            return;
        }

        $this->applyMove($move);
    }

    /** @param array<string, mixed> $source @param array<string, mixed> $target @return array<string, int|string>|null */
    protected function validatedMove(array $source, array $target): ?array
    {
        $userId = Auth::id();
        $sourceType = (string) ($source['type'] ?? '');
        $targetType = (string) ($target['type'] ?? '');
        $sourceIndicatorId = (int) ($source['indicatorId'] ?? 0);
        $targetIndicatorId = (int) ($target['indicatorId'] ?? 0);
        $sourceItemId = (int) ($source['itemId'] ?? 0);
        $targetItemId = (int) ($target['itemId'] ?? 0);

        if ($userId === null || !in_array($sourceType, ['main', 'sub'], true) || !in_array($targetType, ['main', 'sub', 'category'], true)) {
            return null;
        }

        $sourceIndicator = DB::table('ipc_targets_indicators')->where('id', $sourceIndicatorId)->where('user_id', $userId)->first();
        $targetIndicator = $targetIndicatorId > 0
            ? DB::table('ipc_targets_indicators')->where('id', $targetIndicatorId)->where('user_id', $userId)->first()
            : null;
        $targetKra = $targetType === 'category' ? (int) ($target['kra'] ?? 0) : (int) ($targetIndicator->kra_category ?? 0);

        if ($sourceIndicator === null || !in_array($targetKra, $this->allowedKraCategories(), true) || ($targetType !== 'category' && $targetIndicator === null)) {
            return null;
        }

        if ($sourceType === 'sub') {
            $sourceItem = DB::table('ipc_targets_indicators_itemlist')->where('id', $sourceItemId)->where('ind_id', $sourceIndicatorId)->first();
            $targetItem = $targetItemId > 0 ? DB::table('ipc_targets_indicators_itemlist')->where('id', $targetItemId)->where('ind_id', $targetIndicatorId)->first() : null;

            if ($sourceItem === null || ($targetType !== 'category' && $targetItem === null)) {
                return null;
            }

            // A sub-target needs a destination parent, so category headings only accept main targets.
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

    /** @param array<string, int|string> $move */
    protected function applyMove(array $move): void
    {
        DB::transaction(function () use ($move): void {
            if ($move['sourceType'] === 'main') {
                $source = DB::table('ipc_targets_indicators')->where('id', $move['sourceIndicatorId'])->lockForUpdate()->first();

                if ($source === null) {
                    return;
                }

                if ((int) $source->kra_category !== (int) $move['targetKra']) {
                    $targetOrder = $move['targetIndicatorId'] > 0
                        ? DB::table('ipc_targets_indicators')->where('id', $move['targetIndicatorId'])->value('display_order')
                        : null;
                    $newOrder = $targetOrder === null
                        ? ((int) DB::table('ipc_targets_indicators')->where('user_id', $source->user_id)->where('target_year', $source->target_year)->where('kra_category', $move['targetKra'])->max('display_order')) + 1
                        : (int) $targetOrder;

                    if ($targetOrder !== null) {
                        DB::table('ipc_targets_indicators')
                            ->where('user_id', $source->user_id)
                            ->where('target_year', $source->target_year)
                            ->where('kra_category', $move['targetKra'])
                            ->where('display_order', '>=', $newOrder)
                            ->increment('display_order');
                    }

                    DB::table('ipc_targets_indicators')->where('id', $source->id)->update([
                        'kra_category' => $move['targetKra'],
                        'display_order' => $newOrder,
                    ]);

                    return;
                }

                $target = DB::table('ipc_targets_indicators')->where('id', $move['targetIndicatorId'])->lockForUpdate()->first();
                if ($target === null) {
                    return;
                }

                DB::table('ipc_targets_indicators')->where('id', $source->id)->update(['kra_category' => $target->kra_category, 'display_order' => $target->display_order]);
                DB::table('ipc_targets_indicators')->where('id', $target->id)->update(['kra_category' => $source->kra_category, 'display_order' => $source->display_order]);

                return;
            }

            $source = DB::table('ipc_targets_indicators_itemlist')->where('id', $move['sourceItemId'])->lockForUpdate()->first();
            $target = DB::table('ipc_targets_indicators_itemlist')->where('id', $move['targetItemId'])->lockForUpdate()->first();
            if ($source === null || $target === null) {
                return;
            }

            DB::table('ipc_targets_indicators_itemlist')->where('id', $source->id)->update(['ind_id' => $target->ind_id, 'display_order' => $target->display_order]);
            DB::table('ipc_targets_indicators_itemlist')->where('id', $target->id)->update(['ind_id' => $source->ind_id, 'display_order' => $source->display_order]);
        });

        $this->dispatch('annual-target-order-changed');
        Flux::toast(variant: 'success', text: __('Target position updated.'));
    }

    protected function sessionKey(string $name): string
    {
        return 'annual-target.' . $name;
    }

    protected function normalizeSemesterValue(mixed $value): string
    {
        $semester = trim((string) ($value ?? ''));

        return $semester === '-' ? '' : $semester;
    }

    protected function semesterToDatabaseValue(mixed $value): ?int
    {
        $semester = $this->normalizeSemesterValue($value);

        if ($semester === '') {
            return null;
        }

        if (!ctype_digit($semester)) {
            return null;
        }

        return (int) $semester;
    }

    protected function buildSaveRules(): array
    {
        $rules = [
            'editActivity' => ['required', 'string'],
            'editCategory' => ['required', 'in:1,2,3'],
            'editRows' => ['required', 'array', 'min:1'],
        ];

        foreach (array_keys($this->editRows) as $itemId) {
            $prefix = 'editRows.' . $itemId . '.';
            $rules[$prefix . 'semester'] = ['required', 'regex:/^\d+$/'];
            $rules[$prefix . 'description'] = ['required', 'string'];
            $rules[$prefix . 'efficiency'] = ['required', 'string'];
            $rules[$prefix . 'quality'] = ['required', 'string'];
            $rules[$prefix . 'timeliness'] = ['required', 'string'];
            $rules[$prefix . 'movs'] = ['required', 'string'];
            $rules[$prefix . 'remarks'] = ['nullable', 'string'];
        }

        return $rules;
    }

    protected function normalizeTextareaValue(mixed $value): string
    {
        $text = html_entity_decode((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/<br\s*\/?>/i', "\n", $text) ?? '';
    }

    /** @return LengthAwarePaginator<int, stdClass> */
    public function annualTargets(): LengthAwarePaginator
    {
        $userId = Auth::id();

        return app(AnnualTargetDirectory::class)->paginate(
            is_int($userId) ? $userId : null,
            $this->includeStrategicFunction,
            $this->yearFilter,
            $this->categoryFilter,
            $this->semesterFilter,
            trim($this->search),
            $this->perPage,
            $this->showOnlyDuplicates,
        );
    }

    /** @return Collection<int, object> */
    public function years(): Collection
    {
        $currentYear = now()->year;

        return collect(range(2021, $currentYear + 1))
            ->reverse()
            ->values()
            ->map(fn(int $year): object => (object) ['target_year' => (string) $year]);
    }

    /** @return Collection<int, object> */
    public function categories(): Collection
    {
        return collect([
            (object) ['value' => '1', 'label' => 'Strategic Function'],
            (object) ['value' => '2', 'label' => 'Core Function'],
            (object) ['value' => '3', 'label' => 'Support Function'],
        ])->when(!$this->includeStrategicFunction, fn(Collection $categories): Collection => $categories
                ->reject(fn(object $category): bool => $category->value === '1')
                ->values());
    }

    /** @return list<int> */
    protected function allowedKraCategories(): array
    {
        return $this->includeStrategicFunction ? [1, 2, 3] : [2, 3];
    }

    /** @return Collection<int, object> */
    public function semesters(): Collection
    {
        return collect([
            (object) ['value' => '1', 'label' => '1st Semester'],
            (object) ['value' => '2', 'label' => '2nd Semester'],
            (object) ['value' => '3', 'label' => 'Both Semester'],
        ]);
    }

    /** @return Collection<int, object> */
    public function perPageOptions(): Collection
    {
        return collect([
            (object) ['value' => 10, 'label' => '10'],
            (object) ['value' => 20, 'label' => '20'],
            (object) ['value' => 50, 'label' => '50'],
        ]);
    }
}
