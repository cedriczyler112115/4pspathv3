<?php

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

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
    public ?int $editingRowId = null;
    public ?int $editingIndicatorId = null;
    public bool $showDeleteModal = false;
    public ?int $deletingRowId = null;
    public ?int $deletingIndicatorId = null;
    public string $editActivity = '';
    public string $editCategory = '';
    public string $editSemester = '';
    /** @var array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> */
    public array $editRows = [];

    public function mount(): void
    {
        $this->perPage = (int) Session::get($this->sessionKey('perPage'), 10);
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->yearFilter = (string) Session::get($this->sessionKey('yearFilter'), now()->year);
        $this->categoryFilter = (string) Session::get($this->sessionKey('categoryFilter'), '');
        $this->semesterFilter = (string) Session::get($this->sessionKey('semesterFilter'), '');

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
        return view('livewire.pages.annual-target-page', [
            'annualTargets' => $this->annualTargets(),
            'years' => $this->years(),
            'categories' => $this->categories(),
            'semesters' => $this->semesters(),
            'perPageOptions' => $this->perPageOptions(),
        ]);
    }

    public function updatedPerPage(): void
    {
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
        Session::put($this->sessionKey('categoryFilter'), $this->categoryFilter);
        $this->resetPage();
    }

    public function updatedSemesterFilter(): void
    {
        Session::put($this->sessionKey('semesterFilter'), $this->semesterFilter);
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->yearFilter = (string) now()->year;
        $this->categoryFilter = '';
        $this->semesterFilter = '';

        Session::forget([
            $this->sessionKey('search'),
            $this->sessionKey('yearFilter'),
            $this->sessionKey('categoryFilter'),
            $this->sessionKey('semesterFilter'),
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
            ->mapWithKeys(fn (object $item): array => [
                (int) $item->id => [
                    'semester' => (string) ($item->new_semester ?? ''),
                    'description' => (string) ($item->description ?? ''),
                    'efficiency' => (string) ($item->rg_efficiency_ ?? ''),
                    'quality' => (string) ($item->rg_quality_ ?? ''),
                    'timeliness' => (string) ($item->rg_timeliness_ ?? ''),
                    'movs' => (string) ($item->rg_mov_ ?? ''),
                    'remarks' => (string) ($item->rg_remarks_ ?? ''),
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
        $this->editSemester = '';
        $this->editRows = [];
    }

    public function saveEdit(): void
    {
        if ($this->editingRowId === null) {
            return;
        }

        $row = DB::table('ipc_targets_indicators_itemlist as itl')
            ->leftJoin('ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
            ->where('itl.id', $this->editingRowId)
            ->where('iti.user_id', Auth::id())
            ->select(['itl.id'])
            ->first();

        if ($row === null) {
            return;
        }

        foreach ($this->editRows as $itemId => $rowValues) {
            DB::table('ipc_targets_indicators_itemlist')
                ->where('id', $itemId)
                ->update([
                    'new_semester' => filled($rowValues['semester'] ?? '')
                        ? (int) $rowValues['semester']
                        : $this->semesterDefaultValue(),
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
            ->update([
                'activity' => $this->editActivity,
                'kra_category' => $this->editCategory,
            ]);

        DB::table('ipc_targets_indicators_itemlist')
            ->where('id', $this->editingRowId)
            ->update([
                'new_semester' => filled($this->editSemester)
                    ? (int) $this->editSemester
                    : $this->semesterDefaultValue(),
            ]);

        $this->cancelEdit();
        Flux::toast(variant: 'success', text: __('Annual target updated.'));
    }

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
        if ($this->deletingIndicatorId === null) {
            return;
        }

        DB::table('ipc_targets_indicators_itemlist')->where('ind_id', $this->deletingIndicatorId)->delete();

        if ($this->editingIndicatorId === $this->deletingIndicatorId) {
            $this->cancelEdit();
        }

        $this->cancelDelete();

        Flux::toast(variant: 'success', text: __('Annual target deleted.'));
    }

    protected function sessionKey(string $name): string
    {
        return 'annual-target.'.$name;
    }

    protected function semesterDefaultValue(): ?int
    {
        $row = DB::selectOne(
            "SELECT COLUMN_DEFAULT AS default_value
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'ipc_targets_indicators_itemlist'
               AND COLUMN_NAME = 'new_semester'"
        );

        if ($row === null || $row->default_value === null || $row->default_value === '') {
            return null;
        }

        return (int) $row->default_value;
    }

    /** @return LengthAwarePaginator<int, object> */
    public function annualTargets(): LengthAwarePaginator
    {
        $userId = Auth::id();

        if ($userId === null) {
            return new LengthAwarePaginator([], 0, $this->perPage, 1, [
                'path' => request()->url(),
                'pageName' => 'page',
            ]);
        }

        $query = DB::table('ipc_targets_indicators as iti')
            ->leftJoin('ipc_targets_indicators_itemlist as itl', 'itl.ind_id', '=', 'iti.id')
            ->where('iti.user_id', $userId)
            ->where('iti.target_status', '<', 4)
            ->where('itl.indi_status', '<', 4)
            ->select([
                DB::raw('iti.id as tarid'),
                'itl.ind_id',
                'iti.target_group_id',
                'iti.user_id',
                DB::raw('iti.target_sem as target_sem_num'),
                DB::raw('(CASE WHEN iti.target_sem = 1 THEN "1st Semester" WHEN iti.target_sem = 2 THEN "2nd Semester" WHEN iti.target_sem = 3 THEN "Both Semester" END) as target_sem'),
                DB::raw('itl.new_semester as new_semester'),
                'iti.target_year',
                'iti.kra_category',
                'iti.activity',
                'iti.target_status',
                'itl.date_created',
                'iti.created_by',
                'itl.id',
                'itl.description',
                'itl.weight',
                'itl.quantity',
                'itl.quality',
                'itl.timeliness',
                'itl.remarks',
                'itl.rg_efficiency_',
                'itl.rg_quality_',
                'itl.rg_timeliness_',
                'itl.rg_mov_',
                'itl.rg_remarks_',
                'itl.indi_status',
                DB::raw('CASE WHEN iti.kra_category = 1 THEN 2 WHEN iti.kra_category = 2 THEN 1 ELSE 3 END as newsort'),
                DB::raw('(select count(id) from ipc_targets_indicators_itemlist where ind_id = iti.id) as cnt'),
            ])
            ->when($this->yearFilter !== '', function ($query): void {
                $query->where('iti.target_year', $this->yearFilter);
            })
            ->when($this->categoryFilter !== '', function ($query): void {
                $query->where('iti.kra_category', $this->categoryFilter);
            })
            ->when($this->semesterFilter !== '', function ($query): void {
                $query->where('iti.target_sem', $this->semesterFilter);
            })
            ->when(trim($this->search) !== '', function ($query): void {
                $search = trim($this->search);

                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('iti.activity', 'like', '%'.$search.'%')
                        ->orWhere('itl.description', 'like', '%'.$search.'%')
                        ->orWhere('itl.remarks', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('iti.kra_category', 'asc')
            ->orderBy('itl.ind_id', 'asc')
            ->orderBy('iti.date_created', 'asc')
            ->orderBy('itl.date_created', 'asc');

        if ($this->perPage === -1) {
            $items = $query->get();
            $page = max(1, (int) $this->getPage());

            return new LengthAwarePaginator(
                $items,
                $items->count(),
                max(1, $items->count()),
                $page,
                [
                    'path' => request()->url(),
                    'pageName' => 'page',
                ]
            );
        }

        return $query->paginate($this->perPage);
    }

    /** @return Collection<int, object> */
    public function years(): Collection
    {
        $currentYear = now()->year;

        return collect(range(2021, $currentYear + 1))
            ->reverse()
            ->values()
            ->map(fn (int $year): object => (object) ['target_year' => (string) $year]);
    }

    /** @return Collection<int, object> */
    public function categories(): Collection
    {
        return collect([
            (object) ['value' => '1', 'label' => 'Strategic Function'],
            (object) ['value' => '2', 'label' => 'Core Function'],
            (object) ['value' => '3', 'label' => 'Support Function'],
        ]);
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
            (object) ['value' => -1, 'label' => 'All'],
        ]);
    }
}
