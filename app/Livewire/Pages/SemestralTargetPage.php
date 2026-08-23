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

    public function mount(): void
    {
        $this->includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);

        $this->categoryFilter = (string) Session::get($this->sessionKey('categoryFilter'), '');
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->perPage = (int) Session::get($this->sessionKey('perPage'), 10);

        if (!$this->includeStrategicFunction && $this->categoryFilter === '1') {
            $this->categoryFilter = '';
        }

        $this->loadUserProfile();
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
        $semNumber = null;

        if ($this->semId !== null && $this->semId > 0) {
            $semNumber = DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->where('user_id', $userId)
                ->value('semester');
        }

        if ($semNumber === null) {
            $first = DB::table('ipc_sem_targets_indicator as sti')
                ->join('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
                ->where('sem.user_id', $userId)
                ->value('sem.semester');
            $semNumber = $first;
        }

        $semInt = (int) $semNumber;
        if ($semInt === 1) {
            return __('Semestral Target for 1st Semester');
        }
        if ($semInt === 2) {
            return __('Semestral Target for 2nd Semester');
        }

        return __('Semestral Target');
    }

    public function activeSemesterId(): ?int
    {
        if ($this->semId !== null && $this->semId > 0) {
            return $this->semId;
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
    public function handleOpenAddTargetModal(array $payload = []): void
    {
        $kraCategory = (int) ($payload['kraCategory'] ?? $payload['kra'] ?? 1);
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

            DB::table('ipc_sem_targets_indicator_itemlist')->insert([
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

        DB::transaction(function (): void {
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

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $this->deletingSemItemId)
            ->delete();

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
