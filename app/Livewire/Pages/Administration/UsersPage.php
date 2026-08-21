<?php

namespace App\Livewire\Pages\Administration;

use App\Actions\Users\ManageUser;
use App\Services\UserDirectory;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use stdClass;

#[Title('Users')]
class UsersPage extends Component
{
    use WithPagination;

    public int $perPage = 10;

    public string $search = '';

    public string $divisionFilter = '';

    public string $sectionFilter = '';

    public string $statusFilter = '';

    public ?int $deleteUserId = null;

    public string $deleteUserName = '';

    public ?int $editUserId = null;

    public bool $showDeleteModal = false;

    public bool $showEditModal = false;

    public string $editLastName = '';

    public string $editFirstName = '';

    public string $editMiddleName = '';

    public string $editExtensionName = '';

    public string $editPosition = '';

    public string $editDesignation = '';

    public string $editDivision = '';

    public string $editSection = '';

    public string $editSupervisorId = '';

    public string $editUserLevelId = '';

    public string $editContactNumber = '';

    public bool $editIsSupervisor = false;

    protected string $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->divisionFilter = (string) Session::get($this->sessionKey('divisionFilter'), '');
        $this->sectionFilter = (string) Session::get($this->sessionKey('sectionFilter'), '');
        $this->statusFilter = (string) Session::get($this->sessionKey('statusFilter'), '');
        $this->perPage = (int) Session::get($this->sessionKey('perPage'), 10);
    }

    public function updatedSearch(): void
    {
        Session::put($this->sessionKey('search'), $this->search);
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        Session::put($this->sessionKey('perPage'), $this->perPage);
        $this->resetPage();
    }

    public function updatedDivisionFilter(): void
    {
        Session::put($this->sessionKey('divisionFilter'), $this->divisionFilter);
        $this->sectionFilter = '';
        Session::forget($this->sessionKey('sectionFilter'));
        $this->resetPage();
    }

    public function updatedSectionFilter(): void
    {
        Session::put($this->sessionKey('sectionFilter'), $this->sectionFilter);
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        Session::put($this->sessionKey('statusFilter'), $this->statusFilter);
        $this->resetPage();
    }

    protected function sessionKey(string $name): string
    {
        return 'administration.users.'.$name;
    }

    public function edit(int $userId): void
    {
        $user = app(ManageUser::class)->find($userId);

        if (! $user) {
            return;
        }

        $this->editUserId = $userId;
        $this->editLastName = (string) ($user->last_name ?? '');
        $this->editFirstName = (string) ($user->first_name ?? '');
        $this->editMiddleName = (string) ($user->middle_name ?? '');
        $this->editExtensionName = (string) ($user->extension_name ?? '');
        $this->editPosition = (string) ($user->position ?? '');
        $this->editDesignation = (string) ($user->designation ?? '');
        $this->editDivision = (string) ($user->division_id ?? '');
        $this->editSection = (string) ($user->section_id ?? '');
        $this->editSupervisorId = (string) ($user->supervisor_id ?? '');
        $this->editUserLevelId = (string) ($user->user_level_id ?? '');
        $this->editContactNumber = (string) ($user->contact_number ?? '');
        $this->editIsSupervisor = (int) ($user->is_supervisor ?? 0) === 1;
        $this->showEditModal = true;
    }

    public function updatedEditDivision(): void
    {
        $this->editSection = '';
    }

    public function save(): void
    {
        if ($this->editUserId === null) {
            return;
        }

        $data = $this->validate([
            'editLastName' => ['required', 'string', 'max:255'],
            'editFirstName' => ['required', 'string', 'max:255'],
            'editMiddleName' => ['required', 'string', 'max:255'],
            'editExtensionName' => ['nullable', 'string', 'max:10'],
            'editPosition' => ['required', 'string', 'max:100'],
            'editDesignation' => ['required', 'string', 'max:100'],
            'editDivision' => ['required', 'string', Rule::exists('lib_division', 'id')],
            'editSection' => ['required', 'string', Rule::exists('lib_section', 'id')],
            'editSupervisorId' => ['required', 'string', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $this->editUserId))],
            'editUserLevelId' => ['nullable', 'string', Rule::exists('user_level', 'level_id')],
            'editContactNumber' => ['required', 'string', 'max:255'],
            'editIsSupervisor' => ['required', 'boolean'],
        ]);

        app(ManageUser::class)->update($this->editUserId, $data);

        $this->showEditModal = false;
        $this->editUserId = null;
        $this->resetEditForm();
        $this->resetPage();

        Flux::toast(variant: 'success', text: __('User profile updated.'));
    }

    protected function resetEditForm(): void
    {
        $this->editLastName = '';
        $this->editFirstName = '';
        $this->editMiddleName = '';
        $this->editExtensionName = '';
        $this->editPosition = '';
        $this->editDesignation = '';
        $this->editDivision = '';
        $this->editSection = '';
        $this->editSupervisorId = '';
        $this->editUserLevelId = '';
        $this->editContactNumber = '';
        $this->editIsSupervisor = false;
    }

    public function confirmDelete(int $userId): void
    {
        $user = app(ManageUser::class)->find($userId);

        if (! $user) {
            return;
        }

        $this->deleteUserId = $userId;
        $this->deleteUserName = trim(($user->last_name ?? '').(filled($user->last_name) ? ', ' : '').collect([$user->first_name, $user->middle_name])->filter()->join(' '));
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deleteUserId === null) {
            return;
        }

        app(ManageUser::class)->delete($this->deleteUserId);

        $this->deleteUserId = null;
        $this->deleteUserName = '';
        $this->showDeleteModal = false;
        $this->resetPage();
    }

    public function toggleStatus(int $userId): void
    {
        $newStatus = app(ManageUser::class)->toggleStatus($userId);

        if ($newStatus === null) {
            return;
        }

        Flux::toast(
            variant: 'success',
            text: $newStatus === 1
                ? __('User marked as active.')
                : __('User marked as inactive.')
        );
    }

    /** @return LengthAwarePaginator<int, stdClass> */
    public function users(): LengthAwarePaginator
    {
        return app(UserDirectory::class)->administration(
            trim($this->search),
            $this->divisionFilter,
            $this->sectionFilter,
            $this->statusFilter,
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
        return app(UserDirectory::class)->sections($this->divisionFilter, includeDivisionId: true);
    }

    /** @return Collection<int, stdClass> */
    public function editSections(): Collection
    {
        return app(UserDirectory::class)->sections($this->editDivision, includeDivisionId: true);
    }

    /** @return Collection<int, stdClass> */
    public function supervisors(): Collection
    {
        return app(UserDirectory::class)->supervisors();
    }

    /** @return Collection<int, stdClass> */
    public function userLevels(): Collection
    {
        return app(UserDirectory::class)->userLevels();
    }

    public function render(): View
    {
        return view('livewire.pages.administration.users-page', [
            'users' => $this->users(),
            'divisions' => $this->divisions(),
            'sections' => $this->sections(),
            'editSections' => $this->showEditModal ? $this->editSections() : collect(),
            'supervisors' => $this->showEditModal ? $this->supervisors() : collect(),
            'userLevels' => $this->showEditModal ? $this->userLevels() : collect(),
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.users-pagination';
    }
}
