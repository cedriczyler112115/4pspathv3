<?php

namespace App\Livewire\Pages;

use App\Models\ApplicationSetting;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Semestral Target')]
class SemestralTargetPage extends Component
{
    use WithPagination;

    public string $fullName = '';
    public string $position = '';
    public string $designation = '';
    public string $divisionName = '';
    public string $sectionName = '';

    #[Url(as: 'sem_id')]
    public ?int $semId = null;

    public string $search = '';
    public string $categoryFilter = '';
    public int $perPage = 10;
    public bool $includeStrategicFunction = true;

    // Add Target Modal
    public bool $showAddModal = false;
    public ?int $addingKraCategory = null;
    public string $addActivity = '';
    public string $addDescription = '';
    public string $addEfficiency = '';
    public string $addQuality = '';
    public string $addTimeliness = '';
    public string $addMovs = '';
    public string $addRemarks = '';

    // Delete Modals
    public bool $showDeleteModal = false;
    public ?int $deletingSemTargetId = null;
    public bool $showDeleteSubTargetModal = false;
    public ?int $deletingSemItemId = null;

    // Copy Target Modal
    public bool $showCopyModal = false;
    public bool $showCopyAllConfirmModal = false;
    public string $copySourceYear = '';
    public string $copySourceCategory = '';
    public string $copySourceSemester = '';
    public string $copySourceStatusFilter = '';
    public string $copySourceSearch = '';
    public int $copyPage = 1;
    public int $copyPerPage = 5;

    public ?string $unauthorizedErrorMessage = null;

    public function mount(): void
    {
        $this->includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);

        $this->categoryFilter = (string) Session::get($this->sessionKey('categoryFilter'), '');
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->perPage = (int) Session::get($this->sessionKey('perPage'), 10);

        if (!$this->includeStrategicFunction && $this->categoryFilter === '1') {
            $this->categoryFilter = '';
        }

        $this->validateSemId();
        $this->loadUserProfile();

        if ($this->unauthorizedErrorMessage !== null) {
            Flux::toast(variant: 'danger', text: $this->unauthorizedErrorMessage);
        }
    }

    protected function validateSemId(): void
    {
        $userId = Auth::id();

        if ($this->semId !== null && $this->semId > 0 && $userId !== null) {
            $semRecord = DB::table('ipc_semester')->where('id', $this->semId)->first();

            if ($semRecord === null) {
                $this->unauthorizedErrorMessage = __('The requested semestral target record (ID: :id) does not exist.', ['id' => $this->semId]);
            } elseif ((int) $semRecord->user_id !== (int) $userId) {
                $this->unauthorizedErrorMessage = __('Unauthorized access: The requested semestral target record does not belong to your account.');
            }
        }
    }

    protected function loadUserProfile(): void
    {
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

    protected function sessionKey(string $key): string
    {
        return 'semestral_target_page_' . $key;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('search'), $this->search);
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('categoryFilter'), $this->categoryFilter);
    }

    public function updatedPerPage(): void
    {
        if (!in_array((int) $this->perPage, [10, 25, 50, 100, -1], true)) {
            $this->perPage = 10;
        }
        $this->resetPage();
        Session::put($this->sessionKey('perPage'), $this->perPage);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->perPage = 10;

        Session::forget([
            $this->sessionKey('search'),
            $this->sessionKey('categoryFilter'),
            $this->sessionKey('perPage'),
        ]);

        $this->resetPage();
    }

    public function printIpcrf(): void
    {
        Flux::toast(variant: 'info', text: __('Preparing IPCR-F document for print...'));
    }

    public function printCheckpoint(): void
    {
        Flux::toast(variant: 'info', text: __('Preparing Checkpoint document for print...'));
    }

    /**
     * @return Collection<int, object>
     */
    public function categories(): Collection
    {
        return collect([
            (object) ['value' => '1', 'label' => __('Strategic Function')],
            (object) ['value' => '2', 'label' => __('Core Function')],
            (object) ['value' => '3', 'label' => __('Support Function')],
        ])->when(!$this->includeStrategicFunction, fn(Collection $categories): Collection => $categories
            ->reject(fn(object $category): bool => $category->value === '1')
            ->values());
    }

    /**
     * @return array<int, object{value: int, label: string}>
     */
    public function perPageOptions(): array
    {
        return [
            (object) ['value' => 10, 'label' => '10'],
            (object) ['value' => 25, 'label' => '25'],
            (object) ['value' => 50, 'label' => '50'],
            (object) ['value' => 100, 'label' => '100'],
            (object) ['value' => -1, 'label' => 'All'],
        ];
    }

    public function semesterHeading(): string
    {
        $userId = Auth::id();
        $semRecord = null;

        if ($this->semId !== null && $this->semId > 0) {
            $semRecord = DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->where('user_id', $userId)
                ->select(['semester', 'year'])
                ->first();
        }

        if ($semRecord === null) {
            $semRecord = DB::table('ipc_sem_targets_indicator as sti')
                ->join('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
                ->where('sem.user_id', $userId)
                ->select(['sem.semester', 'sem.year'])
                ->first();
        }

        $semInt = (int) ($semRecord->semester ?? 0);
        $year = $semRecord->year ?? null;

        if ($semInt === 1) {
            return $year ? __('Semestral Target for 1st Semester of :year', ['year' => $year]) : __('Semestral Target for 1st Semester');
        }
        if ($semInt === 2) {
            return $year ? __('Semestral Target for 2nd Semester of :year', ['year' => $year]) : __('Semestral Target for 2nd Semester');
        }

        return $year ? __('Semestral Target of :year', ['year' => $year]) : __('Semestral Target');
    }

    public function activeSemesterId(): ?int
    {
        if ($this->unauthorizedErrorMessage !== null) {
            return null;
        }

        if ($this->semId !== null && $this->semId > 0) {
            $isOwner = DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->where('user_id', Auth::id())
                ->exists();

            if ($isOwner) {
                return $this->semId;
            }

            return null;
        }

        $firstSem = DB::table('ipc_semester')
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->value('id');

        return $firstSem ? (int) $firstSem : null;
    }

    #[On('semestral-target-updated')]
    public function refreshTargets(): void
    {
        // Triggers re-render to instantly move targets to their new KRA category sections
    }

    // Add Target Methods
    #[On('open-add-target-modal')]
    public function handleOpenAddTargetModal(mixed $kraCategory = null, mixed $payload = null): void
    {
        $category = 1;
        if (is_numeric($kraCategory)) {
            $category = (int) $kraCategory;
        } elseif (is_array($kraCategory)) {
            $category = (int) ($kraCategory['kraCategory'] ?? $kraCategory['kra'] ?? $kraCategory['category'] ?? 1);
        } elseif (is_array($payload)) {
            $category = (int) ($payload['kraCategory'] ?? $payload['kra'] ?? $payload['category'] ?? 1);
        } elseif (is_numeric($payload)) {
            $category = (int) $payload;
        }

        $this->openAddTargetModal($category);
    }

    public function openAddModal(int $kraCategory): void
    {
        $this->openAddTargetModal($kraCategory);
    }

    public function openAddTargetModal(int $kraCategory): void
    {
        $this->addingKraCategory = $kraCategory;
        $this->addActivity = '';
        $this->addDescription = '';
        $this->addEfficiency = '';
        $this->addQuality = '';
        $this->addTimeliness = '';
        $this->addMovs = '';
        $this->addRemarks = '';
        $this->showAddModal = true;
    }

    public function cancelAdd(): void
    {
        $this->showAddModal = false;
        $this->addingKraCategory = null;
        $this->addActivity = '';
        $this->addDescription = '';
        $this->addEfficiency = '';
        $this->addQuality = '';
        $this->addTimeliness = '';
        $this->addMovs = '';
        $this->addRemarks = '';
    }

    public function saveAdd(): void
    {
        $ipcSemesterId = $this->activeSemesterId();
        $userId = Auth::id();

        if (!$ipcSemesterId || !$userId || !$this->addingKraCategory) {
            Flux::toast(variant: 'danger', text: __('Unable to add target. Semester not found.'));
            return;
        }

        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($ipcSemesterId, $userId, $nowManila): void {
            $maxOrder = DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $ipcSemesterId)
                ->where('kra_category', $this->addingKraCategory)
                ->max('display_order');

            $semTargetId = (int) DB::table('ipc_sem_targets_indicator')->insertGetId([
                'ipc_target_indicator_id' => 0,
                'semester_id' => $ipcSemesterId,
                'kra_category' => $this->addingKraCategory,
                'display_order' => ((int) $maxOrder) + 1,
                'activity' => $this->addActivity,
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

            $semItemId = (int) DB::table('ipc_sem_targets_indicator_itemlist')->insertGetId([
                'target_orig_id' => 0,
                'sem_target_id' => $semTargetId,
                'display_order' => 1,
                'sem_item_id' => $ipcSemesterId,
                'new_semester' => null,
                'description' => $this->addDescription,
                'actual_accomp' => null,
                'weight' => null,
                'quantity' => null,
                'quality' => null,
                'timeliness' => null,
                'rg_quantity' => $this->addEfficiency,
                'rg_quality' => $this->addQuality,
                'rg_timeliness' => $this->addTimeliness,
                'rg_movs' => $this->addMovs,
                'rg_remarks' => $this->addRemarks,
                'remarks' => 1,
                'created_by' => $userId,
                'date_created' => $nowManila,
                'modified_by' => $userId,
                'date_modified' => $nowManila,
            ]);

            $this->logSemTargetHistory($semTargetId, $semItemId, 'activity', null, $this->addActivity, $userId, $nowManila, 'Target Added');
            $this->logSemTargetHistory($semTargetId, $semItemId, 'description', null, $this->addDescription, $userId, $nowManila, 'Target Added');
            if (! blank($this->addEfficiency)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_quantity', null, $this->addEfficiency, $userId, $nowManila, 'Target Added');
            }
            if (! blank($this->addQuality)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_quality', null, $this->addQuality, $userId, $nowManila, 'Target Added');
            }
            if (! blank($this->addTimeliness)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_timeliness', null, $this->addTimeliness, $userId, $nowManila, 'Target Added');
            }
            if (! blank($this->addMovs)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_movs', null, $this->addMovs, $userId, $nowManila, 'Target Added');
            }
            if (! blank($this->addRemarks)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_remarks', null, $this->addRemarks, $userId, $nowManila, 'Target Added');
            }
            $this->logSemTargetHistory($semTargetId, $semItemId, 'created', null, 'Target Created', $userId, $nowManila, 'Target Added');
        });

        $this->cancelAdd();
        Flux::toast(variant: 'success', text: __('Semestral target added successfully.'));
    }

    // Delete Target Events & Methods
    #[On('semestral-target-delete-requested')]
    public function requestDeleteTarget(int $semTargetId): void
    {
        $this->deletingSemTargetId = $semTargetId;
        $this->showDeleteModal = true;
    }

    public function cancelDeleteTarget(): void
    {
        $this->showDeleteModal = false;
        $this->deletingSemTargetId = null;
    }

    public function confirmDeleteTarget(): void
    {
        if ($this->deletingSemTargetId === null) {
            return;
        }

        $userId = Auth::id() ?? 0;
        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($userId, $nowManila): void {
            $targetRow = DB::table('ipc_sem_targets_indicator')
                ->where('id', $this->deletingSemTargetId)
                ->first();

            $itemRows = DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('sem_target_id', $this->deletingSemTargetId)
                ->get();

            foreach ($itemRows as $item) {
                $this->logSemTargetHistory(
                    $this->deletingSemTargetId,
                    (int) $item->id,
                    'deleted',
                    (($targetRow->activity ?? '') ? $targetRow->activity . ' | ' : '') . ($item->description ?? ''),
                    'DELETED',
                    $userId,
                    $nowManila,
                    'Target Deleted'
                );
            }

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('sem_target_id', $this->deletingSemTargetId)
                ->delete();

            DB::table('ipc_sem_targets_indicator')
                ->where('id', $this->deletingSemTargetId)
                ->delete();
        });

        $this->cancelDeleteTarget();
        Flux::toast(variant: 'success', text: __('Semestral target deleted successfully.'));
    }

    // Delete Sub-Target Events & Methods
    #[On('semestral-target-subtarget-delete-requested')]
    public function requestDeleteSubTarget(int $semItemId): void
    {
        $this->deletingSemItemId = $semItemId;
        $this->showDeleteSubTargetModal = true;
    }

    public function cancelDeleteSubTarget(): void
    {
        $this->showDeleteSubTargetModal = false;
        $this->deletingSemItemId = null;
    }

    public function confirmDeleteSubTarget(): void
    {
        if ($this->deletingSemItemId === null) {
            return;
        }

        $userId = Auth::id() ?? 0;
        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($userId, $nowManila): void {
            $itemRow = DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $this->deletingSemItemId)
                ->first();

            if ($itemRow !== null) {
                $this->logSemTargetHistory(
                    (int) $itemRow->sem_target_id,
                    (int) $itemRow->id,
                    'deleted',
                    (string) ($itemRow->description ?? ''),
                    'DELETED',
                    $userId,
                    $nowManila,
                    'Sub-target Deleted'
                );
            }

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $this->deletingSemItemId)
                ->delete();
        });

        $this->cancelDeleteSubTarget();
        Flux::toast(variant: 'success', text: __('Sub-target deleted successfully.'));
    }

    #[On('semestral-target-dropped')]
    public function targetDropped(array $source, array $target): void
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return;
        }

        $sourceType = (string) ($source['type'] ?? '');
        $targetType = (string) ($target['type'] ?? '');
        $sourceIndicatorId = (int) ($source['indicatorId'] ?? 0);
        $targetIndicatorId = (int) ($target['indicatorId'] ?? 0);
        $sourceItemId = (int) ($source['itemId'] ?? 0);
        $targetItemId = (int) ($target['itemId'] ?? 0);

        if ($sourceType === 'main' && $sourceIndicatorId > 0 && $targetIndicatorId > 0 && $sourceIndicatorId !== $targetIndicatorId) {
            DB::transaction(function () use ($sourceIndicatorId, $targetIndicatorId): void {
                $source = DB::table('ipc_sem_targets_indicator')->where('id', $sourceIndicatorId)->lockForUpdate()->first();
                $target = DB::table('ipc_sem_targets_indicator')->where('id', $targetIndicatorId)->lockForUpdate()->first();

                if ($source === null || $target === null) {
                    return;
                }

                $sourceOrder = $source->display_order ?? $source->id;
                $targetOrder = $target->display_order ?? $target->id;

                DB::table('ipc_sem_targets_indicator')->where('id', $source->id)->update([
                    'kra_category' => $target->kra_category,
                    'display_order' => $targetOrder,
                ]);

                DB::table('ipc_sem_targets_indicator')->where('id', $target->id)->update([
                    'kra_category' => $source->kra_category,
                    'display_order' => $sourceOrder,
                ]);
            });

            Flux::toast(variant: 'success', text: __('Target position updated.'));
            return;
        }

        if ($sourceType === 'sub' && $sourceItemId > 0 && $targetItemId > 0 && $sourceItemId !== $targetItemId) {
            DB::transaction(function () use ($sourceItemId, $targetItemId): void {
                $source = DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $sourceItemId)->lockForUpdate()->first();
                $target = DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $targetItemId)->lockForUpdate()->first();

                if ($source === null || $target === null) {
                    return;
                }

                $sourceOrder = $source->display_order ?? $source->id;
                $targetOrder = $target->display_order ?? $target->id;

                DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $source->id)->update([
                    'sem_target_id' => $target->sem_target_id,
                    'display_order' => $targetOrder,
                ]);

                DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $target->id)->update([
                    'sem_target_id' => $source->sem_target_id,
                    'display_order' => $sourceOrder,
                ]);
            });

            Flux::toast(variant: 'success', text: __('Sub-target position updated.'));
        }
    }

    public function semestralTargets(): LengthAwarePaginator
    {
        $userId = Auth::id();

        if ($this->unauthorizedErrorMessage !== null) {
            return new LengthAwarePaginator([], 0, (int) $this->perPage <= 0 ? 10 : (int) $this->perPage, 1);
        }

        $query = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'sti.id', '=', 'stil.sem_target_id')
            ->leftJoin('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->where('sem.user_id', $userId)
            ->when(!$this->includeStrategicFunction, fn($q) => $q->where('sti.kra_category', '!=', 1));

        if ($this->semId !== null && $this->semId > 0) {
            $query->where('sti.semester_id', $this->semId);

            $semNumber = DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->value('semester');

            if ((string) $semNumber === '1') {
                $query->where(function ($q): void {
                    $q->whereNull('stil.new_semester')
                        ->orWhereIn('stil.new_semester', [1, 3]);
                });
            } elseif ((string) $semNumber === '2') {
                $query->where(function ($q): void {
                    $q->whereNull('stil.new_semester')
                        ->orWhereIn('stil.new_semester', [2, 3]);
                });
            }
        }

        if (filled($this->categoryFilter)) {
            $query->where('sti.kra_category', $this->categoryFilter);
        }

        if (filled($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('sti.activity', 'like', $searchTerm)
                    ->orWhere('stil.description', 'like', $searchTerm)
                    ->orWhere('stil.rg_quantity', 'like', $searchTerm)
                    ->orWhere('stil.rg_quality', 'like', $searchTerm)
                    ->orWhere('stil.rg_timeliness', 'like', $searchTerm)
                    ->orWhere('stil.rg_movs', 'like', $searchTerm)
                    ->orWhere('stil.rg_remarks', 'like', $searchTerm);
            });
        }

        $query->select([
            'sti.id as sem_target_id',
            'sti.semester_id',
            'sti.kra_category',
            'sti.display_order as indicator_display_order',
            'sti.activity',
            'stil.id as sem_item_id',
            'stil.display_order as item_display_order',
            'stil.new_semester',
            'stil.description',
            'stil.rg_quantity',
            'stil.rg_quality',
            'stil.rg_timeliness',
            'stil.rg_ratingperiod',
            'stil.rg_movs',
            'stil.rg_remarks',
            'sem.year',
            'sem.semester',
        ])
        ->orderBy('sem.year', 'desc')
        ->orderBy('sem.semester', 'asc')
        ->orderBy('sti.kra_category', 'asc')
        ->orderBy('sti.display_order', 'asc')
        ->orderBy('sti.id', 'asc')
        ->orderBy('stil.display_order', 'asc')
        ->orderBy('stil.id', 'asc');

        $effectivePerPage = (int) $this->perPage <= 0 ? max(1, (clone $query)->count()) : (int) $this->perPage;

        return $query->paginate($effectivePerPage);
    }

    public function openCopyModal(): void
    {
        if ($this->unauthorizedErrorMessage !== null) {
            Flux::toast(variant: 'danger', text: $this->unauthorizedErrorMessage);
            return;
        }

        $this->showCopyModal = true;

        if ($this->copySourceYear === '' || $this->copySourceSemester === '') {
            $activeSemId = $this->activeSemesterId();
            $semRecord = $activeSemId ? DB::table('ipc_semester')->where('id', $activeSemId)->first() : null;

            if ($semRecord && filled($semRecord->year) && filled($semRecord->semester)) {
                $currYear = (int) $semRecord->year;
                $currSem = (int) $semRecord->semester;
            } else {
                $currYear = (int) now()->year;
                $currSem = now()->month >= 7 ? 2 : 1;
            }

            if ($currSem === 2) {
                $prevYear = $currYear;
                $prevSem = 1;
            } else {
                $prevYear = $currYear - 1;
                $prevSem = 2;
            }

            if ($this->copySourceYear === '') {
                $this->copySourceYear = (string) $prevYear;
            }

            if ($this->copySourceSemester === '') {
                $this->copySourceSemester = (string) $prevSem;
            }
        }
    }

    public function closeCopyModal(): void
    {
        $this->showCopyModal = false;
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
    public function semesters(): Collection
    {
        return collect([
            (object) ['value' => '1', 'label' => __('1st Semester')],
            (object) ['value' => '2', 'label' => __('2nd Semester')],
            (object) ['value' => '3', 'label' => __('Both Semester')],
        ]);
    }

    public function copySourceYears(): Collection
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return $this->years();
        }

        $years = DB::table('ipc_semester')
            ->where('user_id', $userId)
            ->whereNotNull('year')
            ->select('year as target_year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->get()
            ->map(fn(object $row): object => (object) ['target_year' => (string) $row->target_year]);

        return $years->isNotEmpty() ? $years : $this->years();
    }

    /** @return array<int, string> */
    public function getExistingActivitiesProperty(): array
    {
        $ipcSemesterId = $this->activeSemesterId();
        if (!$ipcSemesterId) {
            return [];
        }

        return DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $ipcSemesterId)
            ->pluck('activity')
            ->map(fn($act) => trim(mb_strtolower((string) $act)))
            ->filter()
            ->all();
    }

    public function updatedCopySourceYear(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceCategory(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceSemester(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceStatusFilter(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceSearch(): void
    {
        $this->copyPage = 1;
    }

    public function previousCopyPage(): void
    {
        if ($this->copyPage > 1) {
            $this->copyPage--;
        }
    }

    public function nextCopyPage(): void
    {
        $this->copyPage++;
    }

    public function copySemestralTargetGroups(bool $paginate = true): LengthAwarePaginator|Collection
    {
        $userId = Auth::id();
        $activeSemId = $this->activeSemesterId();

        if (!$userId) {
            return $paginate ? new LengthAwarePaginator([], 0, max(1, $this->copyPerPage), 1) : collect();
        }

        $query = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'stil.sem_target_id', '=', 'sti.id')
            ->leftJoin('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->where('sem.user_id', $userId)
            ->where('sti.target_status', 3)
            ->where('stil.remarks', 3);

        if ($activeSemId) {
            $query->where('sti.semester_id', '!=', $activeSemId);
        }

        if ($this->copySourceYear !== '') {
            $query->where('sem.year', $this->copySourceYear);
        }

        if ($this->copySourceCategory !== '') {
            $query->where('sti.kra_category', (int) $this->copySourceCategory);
        }

        if ($this->copySourceSemester !== '') {
            $query->where(function ($q): void {
                $q->where('sem.semester', (int) $this->copySourceSemester)
                    ->orWhere('stil.new_semester', (int) $this->copySourceSemester);
            });
        }

        if (trim($this->copySourceSearch) !== '') {
            $search = '%' . trim($this->copySourceSearch) . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('sti.activity', 'like', $search)
                    ->orWhere('stil.description', 'like', $search);
            });
        }

        $rows = $query->select([
            'sti.id as ind_id',
            'sti.kra_category',
            'sti.activity',
            'sem.year as target_year',
            'sem.semester as target_sem',
            'stil.id as item_id',
            'stil.new_semester',
            'stil.description',
            'stil.rg_quantity',
            'stil.rg_quality',
            'stil.rg_timeliness',
            'stil.rg_ratingperiod',
            'stil.rg_movs',
            'stil.rg_remarks',
        ])
            ->orderBy('sem.year', 'desc')
            ->orderBy('sem.semester', 'asc')
            ->orderBy('sti.kra_category', 'asc')
            ->orderBy('sti.display_order', 'asc')
            ->orderBy('stil.display_order', 'asc')
            ->get();

        $grouped = $rows->groupBy('ind_id');

        if (!$paginate) {
            return $grouped;
        }

        $total = $grouped->count();
        $perPage = max(1, $this->copyPerPage);
        $lastPage = (int) ceil($total / $perPage) ?: 1;
        $currentPage = max(1, min($this->copyPage, $lastPage));
        if ($this->copyPage !== $currentPage) {
            $this->copyPage = $currentPage;
        }

        $pagedItems = $grouped->slice(($currentPage - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $pagedItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'copyPage',
            ]
        );
    }

    public function requestCopyAllSemestral(): void
    {
        $this->showCopyAllConfirmModal = true;
    }

    public function cancelCopyAllConfirm(): void
    {
        $this->showCopyAllConfirmModal = false;
    }

    public function confirmCopyAll(): void
    {
        $this->showCopyAllConfirmModal = false;
        $this->copyAllSemestralTargetGroups();
    }

    public function copyAllSemestralTargetGroups(): void
    {
        $groups = $this->copySemestralTargetGroups(false);
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

            $this->copySemestralTargetGroup((int) $indicatorId);
            $existingActivities[] = $activityClean;
            $copiedCount++;
        }

        if ($copiedCount > 0) {
            Flux::toast(variant: 'success', text: __(':count semestral target group(s) copied successfully.', ['count' => $copiedCount]));
        } else {
            Flux::toast(variant: 'warning', text: __('No new semestral targets available to copy (all matching results already exist).'));
        }
    }

    public function copySemestralTargetGroup(int $indicatorId): void
    {
        $ipcSemesterId = $this->activeSemesterId();
        $userId = Auth::id();

        if (!$ipcSemesterId || !$userId) {
            Flux::toast(variant: 'danger', text: __('Unable to copy target. Semester not found.'));
            return;
        }

        $sourceIndicator = DB::table('ipc_sem_targets_indicator')
            ->where('id', $indicatorId)
            ->where('target_status', 3)
            ->first();
        if ($sourceIndicator === null) {
            return;
        }

        $sourceItems = DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('sem_target_id', $indicatorId)
            ->where('remarks', 3)
            ->get();

        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($sourceIndicator, $sourceItems, $ipcSemesterId, $userId, $nowManila): void {
            $maxOrder = (int) DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $ipcSemesterId)
                ->where('kra_category', $sourceIndicator->kra_category)
                ->max('display_order');

            $newSemTargetId = (int) DB::table('ipc_sem_targets_indicator')->insertGetId([
                'ipc_target_indicator_id' => $sourceIndicator->ipc_target_indicator_id ?? 0,
                'semester_id' => $ipcSemesterId,
                'kra_category' => $sourceIndicator->kra_category,
                'display_order' => $maxOrder + 1,
                'activity' => $sourceIndicator->activity,
                'verified' => null,
                'verified_by' => null,
                'date_verified' => null,
                'remarks' => $sourceIndicator->remarks,
                'target_status' => 1,
                'created_by' => $userId,
                'date_created' => $nowManila,
                'modified_by' => $userId,
                'last_date_modified' => $nowManila,
                'target_from' => $userId,
            ]);

            foreach ($sourceItems as $item) {
                $newItemId = (int) DB::table('ipc_sem_targets_indicator_itemlist')->insertGetId([
                    'target_orig_id' => $item->target_orig_id ?? 0,
                    'sem_target_id' => $newSemTargetId,
                    'display_order' => $item->display_order,
                    'sem_item_id' => $ipcSemesterId,
                    'new_semester' => $item->new_semester,
                    'description' => $item->description,
                    'actual_accomp' => null,
                    'weight' => $item->weight,
                    'quantity' => $item->quantity,
                    'quality' => $item->quality,
                    'timeliness' => $item->timeliness,
                    'rg_quantity' => $item->rg_quantity,
                    'rg_quality' => $item->rg_quality,
                    'rg_timeliness' => $item->rg_timeliness,
                    'rg_ratingperiod' => $item->rg_ratingperiod,
                    'rg_movs' => $item->rg_movs,
                    'rg_remarks' => $item->rg_remarks,
                    'remarks' => 1,
                    'created_by' => $userId,
                    'date_created' => $nowManila,
                    'modified_by' => $userId,
                    'date_modified' => $nowManila,
                ]);

                $this->logSemTargetHistory($newSemTargetId, $newItemId, 'created', null, (string) ($item->description ?? ''), $userId, $nowManila, 'Target Copied');
            }
        });

        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Target copied successfully.'));
    }

    protected function logSemTargetHistory(
        int $semTargetId,
        ?int $semItemId,
        string $fieldName,
        ?string $oldVal,
        ?string $newVal,
        int $userId,
        \Illuminate\Support\Carbon $now,
        ?string $justification = null
    ): void {
        DB::table('ipc_sem_target_edit_histories')->insert([
            'sem_target_id' => $semTargetId,
            'sem_item_id' => $semItemId,
            'field_name' => $fieldName,
            'original_value' => $oldVal,
            'old_value' => $oldVal,
            'new_value' => $newVal,
            'last_edited_value' => $oldVal,
            'justification' => $justification,
            'user_id' => $userId,
            'date_created' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function render(): View
    {
        $categories = $this->categories();
        $visibleCategories = $this->categoryFilter === ''
            ? $categories
            : $categories->where('value', $this->categoryFilter)->values();

        return view('livewire.pages.semestral-target-page', [
            'semestralTargets' => $this->semestralTargets(),
            'categories' => $categories,
            'visibleCategories' => $visibleCategories,
            'semesterHeading' => $this->semesterHeading(),
        ]);
    }
}
