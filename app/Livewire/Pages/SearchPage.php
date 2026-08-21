<?php

namespace App\Livewire\Pages;

use App\Services\UserDirectory;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use stdClass;

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

    /** @return LengthAwarePaginator<int, stdClass> */
    public function users(): LengthAwarePaginator
    {
        return app(UserDirectory::class)->search(
            $this->appliedSearch,
            $this->appliedDivisionFilter,
            $this->appliedSectionFilter,
            $this->perPage,
        );
    }

    /** @return Collection<int, stdClass> */
    public function divisions(): Collection
    {
        return app(UserDirectory::class)->divisions();
    }

    /** @return Collection<int, stdClass> */
    public function sections(): Collection
    {
        return app(UserDirectory::class)->sections($this->divisionFilter);
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
