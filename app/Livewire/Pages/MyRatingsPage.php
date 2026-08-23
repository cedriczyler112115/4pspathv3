<?php

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Livewire\WithPagination;

class MyRatingsPage extends Component
{
    use WithPagination;

    public string $fullName = '';
    public string $position = '';
    public string $designation = '';
    public string $divisionName = '';
    public string $sectionName = '';

    public string $search = '';
    public string $yearFilter = '';
    public string $semesterFilter = '';
    public int $perPage = 10;

    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    public bool $showViewModal = false;
    public ?object $viewingRating = null;

    public function mount(): void
    {
        $this->yearFilter = (string) Session::get($this->sessionKey('yearFilter'), '');
        $this->semesterFilter = (string) Session::get($this->sessionKey('semesterFilter'), '');
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->perPage = (int) Session::get($this->sessionKey('perPage'), 10);

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
        return 'my_ratings_page_' . $key;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('search'), $this->search);
    }

    public function updatedYearFilter(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('yearFilter'), $this->yearFilter);
    }

    public function updatedSemesterFilter(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('semesterFilter'), $this->semesterFilter);
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('perPage'), $this->perPage);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->yearFilter = '';
        $this->semesterFilter = '';
        $this->perPage = 10;

        Session::forget([
            $this->sessionKey('search'),
            $this->sessionKey('yearFilter'),
            $this->sessionKey('semesterFilter'),
            $this->sessionKey('perPage'),
        ]);

        $this->resetPage();
    }

    /**
     * @return array<int, object{target_year: string}>
     */
    public function years(): array
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return [];
        }

        return DB::table('ipc_semester')
            ->where('user_id', $userId)
            ->whereNotNull('year')
            ->select('year as target_year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->get()
            ->map(fn(object $row): object => (object) ['target_year' => (string) $row->target_year])
            ->all();
    }

    /**
     * @return array<int, object{value: string, label: string}>
     */
    public function semesters(): array
    {
        return [
            (object) ['value' => '1', 'label' => __('1st Semester')],
            (object) ['value' => '2', 'label' => __('2nd Semester')],
        ];
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
        ];
    }

    public function myRatings(): LengthAwarePaginator
    {
        $userId = Auth::id();

        $query = DB::table('ipc_semester')
            ->where('user_id', $userId);

        if (filled($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('year', 'like', $searchTerm)
                    ->orWhere('final_rating', 'like', $searchTerm)
                    ->orWhere('adjectival_rating', 'like', $searchTerm)
                    ->orWhere('overall_remarks', 'like', $searchTerm);
            });
        }

        if (filled($this->yearFilter)) {
            $query->where('year', $this->yearFilter);
        }

        if (filled($this->semesterFilter)) {
            $query->where('semester', $this->semesterFilter);
        }

        return $query->orderBy('year', 'desc')
            ->orderBy('semester', 'asc')
            ->paginate($this->perPage);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->deletingId = null;
        $this->showDeleteModal = false;
    }

    public function deleteRating(): void
    {
        $userId = Auth::id();
        if (!is_int($userId) || $this->deletingId === null) {
            $this->cancelDelete();

            return;
        }

        DB::table('ipc_semester')
            ->where('id', $this->deletingId)
            ->where('user_id', $userId)
            ->delete();

        $this->cancelDelete();

        \Flux::toast(variant: 'success', text: __('Semester rating record removed successfully.'));
    }

    public function viewRating(int $id): void
    {
        $this->redirect(route('myratings.semestral-target', ['sem_id' => $id]), navigate: true);
    }

    public function cancelView(): void
    {
        $this->viewingRating = null;
        $this->showViewModal = false;
    }

    public function render(): View
    {
        return view('livewire.pages.my-ratings-page', [
            'myRatings' => $this->myRatings(),
            'years' => $this->years(),
            'semesters' => $this->semesters(),
            'perPageOptions' => $this->perPageOptions(),
        ]);
    }
}
