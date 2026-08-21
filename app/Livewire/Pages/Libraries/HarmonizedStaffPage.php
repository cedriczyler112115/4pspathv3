<?php

namespace App\Livewire\Pages\Libraries;

use App\Models\LibHarmonizedPosition;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Harmonized Staff')]
class HarmonizedStaffPage extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public string $search = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $name = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        if (! in_array($this->perPage, [10, 20, 50], true)) {
            $this->perPage = 10;
        }

        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $maxSort = (int) LibHarmonizedPosition::query()->max('sort_order');
        $this->sort_order = $maxSort + 1;
        $this->is_active = true;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $position = LibHarmonizedPosition::query()->find($id);

        if (! $position) {
            return;
        }

        $this->editingId = $position->id;
        $this->name = $position->name;
        $this->sort_order = (int) $position->sort_order;
        $this->is_active = (bool) $position->is_active;
        $this->resetValidation();
        $this->showFormModal = true;
    }

    public function cancelForm(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lib_harmonized_positions', 'name')->ignore($this->editingId),
            ],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($this->editingId !== null) {
            $position = LibHarmonizedPosition::query()->find($this->editingId);

            if ($position) {
                $position->update($validated);
                Flux::toast(variant: 'success', text: __('Position updated successfully.'));
            }
        } else {
            LibHarmonizedPosition::query()->create($validated);
            Flux::toast(variant: 'success', text: __('Position created successfully.'));
        }

        $this->cancelForm();
    }

    public function confirmDelete(int $id): void
    {
        $position = LibHarmonizedPosition::query()->find($id);

        if (! $position) {
            return;
        }

        $this->deletingId = $position->id;
        $this->showDeleteModal = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        $position = LibHarmonizedPosition::query()->find($this->deletingId);

        if ($position) {
            $position->delete();
            Flux::toast(variant: 'success', text: __('Position deleted successfully.'));
        }

        $this->cancelDelete();
    }

    public function render(): View
    {
        return view('livewire.pages.libraries.harmonized-staff-page', [
            'positions' => $this->positions(),
            'perPageOptions' => $this->perPageOptions(),
        ]);
    }

    /** @return LengthAwarePaginator<int, LibHarmonizedPosition> */
    public function positions(): LengthAwarePaginator
    {
        $query = LibHarmonizedPosition::query();

        if (trim($this->search) !== '') {
            $like = '%'.trim($this->search).'%';
            $query->where('name', 'like', $like);
        }

        return $query
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($this->perPage);
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

    protected function resetForm(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->name = '';
        $this->sort_order = 0;
        $this->is_active = true;
    }
}
