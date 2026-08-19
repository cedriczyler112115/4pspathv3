<?php

namespace App\Livewire\Pages\Libraries\Users;

use App\Models\UserLevel;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('User Level')]
class UserLevelPage extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public string $search = '';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public ?int $pendingId = null;

    public string $level_name = '';

    public string $is_status = '1';

    public bool $showInlineEdit = false;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->perPage = 10;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditor(): void
    {
        $id = (int) ($this->pendingId ?? 0);
        $row = UserLevel::query()->where('level_id', $id)->first();

        if (! $row) {
            return;
        }

        $this->editingId = $row->level_id;
        $this->level_name = $row->level_name;
        $this->is_status = (string) ((int) ($row->is_status ?? 1));
        $this->showInlineEdit = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'level_name' => [
                'required',
                'string',
                'max:50',
                'unique:user_level,level_name'.($this->editingId ? ','.$this->editingId.',level_id' : ''),
            ],
            'is_status' => ['required', 'in:0,1'],
        ]);

        $payload = [
            'level_name' => $data['level_name'],
            'is_status' => (int) $data['is_status'],
        ];

        if ($this->editingId) {
            UserLevel::query()->where('level_id', $this->editingId)->update($payload);
            Flux::toast(variant: 'success', text: __('User level updated.'));
        } else {
            UserLevel::query()->create($payload);
            Flux::toast(variant: 'success', text: __('User level created.'));
        }

        $this->showInlineEdit = false;
        $this->resetForm();
        $this->resetPage();
    }

    public function openDeleteConfirm(): void
    {
        $id = (int) ($this->pendingId ?? 0);
        $row = UserLevel::query()->where('level_id', $id)->first();

        if (! $row) {
            return;
        }

        $this->deletingId = $row->level_id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId === null) {
            return;
        }

        UserLevel::query()->where('level_id', $this->deletingId)->delete();

        $this->deletingId = null;
        $this->showDeleteModal = false;
        $this->resetPage();

        Flux::toast(variant: 'success', text: __('User level deleted.'));
    }

    public function cancel(): void
    {
        $this->showFormModal = false;
        $this->showDeleteModal = false;
        $this->showInlineEdit = false;
        $this->pendingId = null;
        $this->resetForm();
    }

    /** @return LengthAwarePaginator<int, UserLevel> */
    public function userLevels(): LengthAwarePaginator
    {
        return UserLevel::query()
            ->when(trim($this->search) !== '', function ($query): void {
                $search = trim($this->search);
                $query->where(function ($subQuery) use ($search): void {
            $subQuery->where('level_name', 'like', '%'.$search.'%')
                        ->orWhere('level_id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('level_name')
            ->paginate($this->perPage);
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

    public function paginationView(): string
    {
        return 'vendor.pagination.users-pagination';
    }

    public function render(): View
    {
        return view('livewire.pages.libraries.users.user-level-page', [
            'userLevels' => $this->userLevels(),
        ]);
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->deletingId = null;
        $this->pendingId = null;
        $this->level_name = '';
        $this->is_status = '1';
        $this->showInlineEdit = false;
    }
}
