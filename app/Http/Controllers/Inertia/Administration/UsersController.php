<?php

namespace App\Http\Controllers\Inertia\Administration;

use App\Actions\Users\ManageUser;
use App\Http\Controllers\Controller;
use App\Services\UserDirectory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    public function index(Request $request, UserDirectory $directory): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'division' => (string) $request->string('division'),
            'section' => (string) $request->string('section'),
            'user_level_id' => (string) ($request->string('user_level_id') ?: $request->string('userLevel')),
            'status' => (string) $request->string('status'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $users = $directory->administration(
            $filters['search'],
            $filters['division'],
            $filters['section'],
            $filters['status'],
            $filters['perPage'],
            $filters['user_level_id'],
        )->withQueryString()->through(fn ($u) => [
            'id' => (int) $u->id,
            'lastName' => (string) ($u->last_name ?? ''),
            'firstName' => (string) ($u->first_name ?? ''),
            'middleName' => (string) ($u->middle_name ?? ''),
            'extensionName' => (string) ($u->extension_name ?? ''),
            'fullName' => trim(collect([$u->last_name, $u->first_name, $u->middle_name, $u->extension_name])->filter()->join(' ')),
            'email' => (string) ($u->email ?? ''),
            'contactNumber' => $u->contact_number,
            'position' => $u->position,
            'designation' => $u->designation,
            'divisionId' => (string) ($u->division_id ?? ''),
            'divisionName' => $u->division_name ?? $u->division ?? '',
            'sectionId' => (string) ($u->section_id ?? ''),
            'sectionName' => $u->section_name ?? '',
            'supervisorId' => (string) ($u->supervisor_id ?? ''),
            'userLevelId' => (string) ($u->user_level_id ?? ''),
            'userLevelName' => $u->user_level_name ?? null,
            'isSupervisor' => (bool) ($u->is_supervisor ?? false),
            'isStatus' => (int) $u->is_status,
        ]);

        return Inertia::render('Administration/Users', [
            'filters' => $filters,
            'users' => $users,
            'divisions' => array_map(fn ($d) => [
                'id' => (string) $d->id,
                'name' => (string) ($d->division_name ?? $d->name ?? ''),
            ], $directory->divisions()->all()),
            'sections' => array_map(fn ($s) => [
                'id' => (string) $s->id,
                'divisionId' => (string) ($s->division_id ?? ''),
                'name' => (string) ($s->section_name ?? $s->name ?? ''),
            ], $directory->sections('', includeDivisionId: true)->all()),
            'supervisors' => array_map(fn ($sp) => [
                'id' => (string) $sp->id,
                'name' => trim(collect([$sp->last_name, $sp->first_name, $sp->middle_name])->filter()->join(' ')),
            ], $directory->supervisors()->all()),
            'userLevels' => array_map(fn ($ul) => [
                'id' => (string) $ul->level_id,
                'name' => (string) $ul->level_name,
            ], $directory->userLevels()->all()),
            'statusOptions' => [
                ['value' => '', 'label' => 'All Statuses'],
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ],
            'perPageOptions' => [
                ['value' => 10, 'label' => '10'],
                ['value' => 20, 'label' => '20'],
                ['value' => 50, 'label' => '50'],
            ],
        ]);
    }

    public function update(Request $request, ManageUser $manageUser, int $userId): RedirectResponse
    {
        $data = $request->validate([
            'editLastName' => ['required', 'string', 'max:255'],
            'editFirstName' => ['required', 'string', 'max:255'],
            'editMiddleName' => ['required', 'string', 'max:255'],
            'editExtensionName' => ['nullable', 'string', 'max:10'],
            'editPosition' => ['required', 'string', 'max:100'],
            'editDesignation' => ['required', 'string', 'max:100'],
            'editDivision' => ['required', 'string', Rule::exists('lib_division', 'id')],
            'editSection' => ['required', 'string', Rule::exists('lib_section', 'id')],
            'editSupervisorId' => ['nullable', 'string', Rule::exists('users', 'id')->where(fn ($query) => $query->where('id', '!=', $userId))],
            'editUserLevelId' => ['nullable', 'string', Rule::exists('user_level', 'level_id')],
            'editContactNumber' => ['required', 'string', 'max:255'],
            'editIsSupervisor' => ['required', 'boolean'],
        ]);

        $manageUser->update($userId, $data);

        return back()->with('success', __('User profile updated.'));
    }

    public function destroy(ManageUser $manageUser, int $userId): RedirectResponse
    {
        $manageUser->delete($userId);

        return back()->with('success', __('User deleted.'));
    }

    public function toggleStatus(ManageUser $manageUser, int $userId): RedirectResponse
    {
        $newStatus = $manageUser->toggleStatus($userId);

        return back()->with('success', $newStatus === 1 ? __('User marked as active.') : __('User marked as inactive.'));
    }
}
