<?php

namespace App\Livewire\Pages;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Search Users')]
class SearchPage extends Component
{
    use WithPagination;

    public string $search = '';

    public string $divisionFilter = '';

    public string $sectionFilter = '';

    public string $appliedSearch = '';

    public string $appliedDivisionFilter = '';

    public string $appliedSectionFilter = '';

    public int $perPage = 10;

    protected string $paginationTheme = 'tailwind';

    public function updatedDivisionFilter(): void
    {
        $this->sectionFilter = '';
    }

    public function applyFilters(): void
    {
        $this->appliedSearch = trim($this->search);
        $this->appliedDivisionFilter = $this->divisionFilter;
        $this->appliedSectionFilter = $this->sectionFilter;
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    /** @return LengthAwarePaginator<int, object> */
    public function users(): LengthAwarePaginator
    {
        return DB::table('users')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->select([
                'users.id',
                'users.last_name',
                'users.first_name',
                'users.middle_name',
                'users.extension_name',
                'users.email',
                'users.contact_number',
                'users.position',
                DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
            ])
            ->when($this->appliedSearch !== '', function ($query): void {
                $search = '%'.$this->appliedSearch.'%';

                $query->where(function ($nameQuery) use ($search): void {
                    $nameQuery
                        ->whereRaw("CONCAT_WS(' ', users.last_name, users.first_name, users.middle_name, users.extension_name) LIKE ?", [$search])
                        ->orWhereRaw("CONCAT_WS(' ', users.first_name, users.middle_name, users.last_name, users.extension_name) LIKE ?", [$search]);
                });
            })
            ->when($this->appliedDivisionFilter !== '', fn ($query) => $query->where('users.division_id', $this->appliedDivisionFilter))
            ->when($this->appliedSectionFilter !== '', fn ($query) => $query->where('users.section_id', $this->appliedSectionFilter))
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->paginate($this->perPage);
    }

    /** @return Collection<int, object> */
    public function divisions(): Collection
    {
        return DB::table('lib_division')
            ->orderBy('division_name')
            ->get(['id', 'division_name']);
    }

    /** @return Collection<int, object> */
    public function sections(): Collection
    {
        return DB::table('lib_section')
            ->when($this->divisionFilter !== '', fn ($query) => $query->where('division_id', $this->divisionFilter))
            ->orderBy('section_name')
            ->get(['id', 'section_name']);
    }

    public function render(): View
    {
        return view('livewire.pages.search-page', [
            'users' => $this->users(),
            'divisions' => $this->divisions(),
            'sections' => $this->sections(),
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.users-pagination';
    }
}
