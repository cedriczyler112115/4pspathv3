<?php

namespace App\Livewire\Pages\Administration;

use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

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
    public string $editContactNumber = '';
    public string $editIsSupervisor = '0';

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
        $user = DB::table('users')->where('id', $userId)->first();

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
        $this->editContactNumber = (string) ($user->contact_number ?? '');
        $this->editIsSupervisor = (string) ((int) ($user->is_supervisor ?? 0));
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
            'editLastName' => ['nullable', 'string', 'max:255'],
            'editFirstName' => ['nullable', 'string', 'max:255'],
            'editMiddleName' => ['nullable', 'string', 'max:255'],
            'editExtensionName' => ['nullable', 'string', 'max:10'],
            'editPosition' => ['nullable', 'string', 'max:100'],
            'editDesignation' => ['nullable', 'string', 'max:100'],
            'editDivision' => ['nullable', 'string', Rule::exists('lib_division', 'id')],
            'editSection' => ['nullable', 'string', Rule::exists('lib_section', 'id')],
            'editSupervisorId' => ['nullable', 'string', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $this->editUserId))],
            'editContactNumber' => ['nullable', 'string', 'max:255'],
            'editIsSupervisor' => ['required', Rule::in(['0', '1'])],
        ]);

        DB::table('users')
            ->where('id', $this->editUserId)
            ->update([
                'last_name' => $data['editLastName'] ?: null,
                'first_name' => $data['editFirstName'] ?: null,
                'middle_name' => $data['editMiddleName'] ?: null,
                'extension_name' => $data['editExtensionName'] ?: null,
                'position' => $data['editPosition'] ?: null,
                'designation' => $data['editDesignation'] ?: null,
                'division_id' => $data['editDivision'] !== '' ? $data['editDivision'] : null,
                'section_id' => $data['editSection'] !== '' ? $data['editSection'] : null,
                'supervisor_id' => $data['editSupervisorId'] !== '' ? $data['editSupervisorId'] : null,
                'contact_number' => $data['editContactNumber'] ?: null,
                'is_supervisor' => $data['editIsSupervisor'],
                'date_modified' => now(),
            ]);

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
        $this->editContactNumber = '';
        $this->editIsSupervisor = '0';
    }

    public function confirmDelete(int $userId): void
    {
        $user = DB::table('users')->where('id', $userId)->first(['last_name', 'first_name', 'middle_name']);

        if (! $user) {
            return;
        }

        $this->deleteUserId = $userId;
        $this->deleteUserName = trim(($user->last_name ?? '') . (filled($user->last_name) ? ', ' : '') . collect([$user->first_name, $user->middle_name])->filter()->join(' '));
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deleteUserId === null) {
            return;
        }

        DB::table('users')->where('id', $this->deleteUserId)->delete();

        $this->deleteUserId = null;
        $this->deleteUserName = '';
        $this->showDeleteModal = false;
        $this->resetPage();
    }

    public function toggleStatus(int $userId): void
    {
        $user = DB::table('users')->where('id', $userId)->first(['is_status']);

        if (! $user) {
            return;
        }

        $newStatus = ((int) $user->is_status === 1) ? 0 : 1;

        DB::table('users')
            ->where('id', $userId)
            ->update([
                'is_status' => $newStatus,
                'date_modified' => now(),
                'activated_at' => $newStatus === 1 ? now() : null,
                'deactivated_at' => $newStatus === 0 ? now() : null,
            ]);

        Flux::toast(
            variant: 'success',
            text: $newStatus === 1
                ? __('User marked as active.')
                : __('User marked as inactive.')
        );
    }

    /** @return LengthAwarePaginator<int, object> */
    public function users(): LengthAwarePaginator
    {
        $query = DB::table('users')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->select([
                'users.id',
                'users.last_name',
                'users.first_name',
                'users.middle_name',
                'users.email',
                'users.contact_number',
                'users.position',
                'users.designation',
                'users.division',
                'users.section',
                'users.is_status',
                DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
            ])
            ->when(trim($this->search) !== '', function ($query): void {
                $search = trim($this->search);

                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->whereRaw(
                        "CONCAT_WS(' ', users.last_name, users.first_name, users.middle_name) like ?",
                        ['%'.$search.'%']
                    )
                        ->orWhere('users.position', 'like', '%'.$search.'%')
                        ->orWhere('users.designation', 'like', '%'.$search.'%');
                });
            })
            ->when($this->divisionFilter !== '', function ($query): void {
                $query->where('users.division_id', $this->divisionFilter);
            })
            ->when($this->sectionFilter !== '', function ($query): void {
                $query->where('users.section_id', $this->sectionFilter);
            })
            ->when($this->statusFilter !== '', function ($query): void {
                $query->where('users.is_status', $this->statusFilter);
            })
            ->orderByDesc('users.id');

        return $query->paginate($this->perPage);
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
            ->when($this->divisionFilter !== '', function ($query): void {
                $query->where('division_id', $this->divisionFilter);
            })
            ->orderBy('section_name')
            ->get(['id', 'section_name', 'division_id']);
    }

    /** @return Collection<int, object> */
    public function editSections(): Collection
    {
        return DB::table('lib_section')
            ->when($this->editDivision !== '', function ($query): void {
                $query->where('division_id', $this->editDivision);
            })
            ->orderBy('section_name')
            ->get(['id', 'section_name', 'division_id']);
    }

    /** @return Collection<int, object> */
    public function supervisors(): Collection
    {
        return DB::table('users')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name']);
    }

    public function render(): View
    {
        return view('livewire.pages.administration.users-page', [
            'users' => $this->users(),
            'divisions' => $this->divisions(),
            'sections' => $this->sections(),
            'editSections' => $this->editSections(),
            'supervisors' => $this->supervisors(),
        ]);
    }

    public function paginationView(): string
    {
        return 'vendor.pagination.users-pagination';
    }
}
