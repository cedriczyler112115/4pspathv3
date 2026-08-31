<?php

namespace App\Http\Controllers\Inertia\Settings;

use App\Http\Controllers\Controller;
use App\Services\UserDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class MyStaffController extends Controller
{
    public function index(Request $request, UserDirectory $directory): Response
    {
        $userId = (int) Auth::id();

        $filters = [
            'search' => (string) $request->string('search'),
            'status' => (string) $request->string('status'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $staff = $directory->supervisedStaff(
            $userId,
            $filters['search'],
            '',
            '',
            $filters['status'],
            $filters['perPage'],
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

        return Inertia::render('Settings/MyStaff', [
            'filters' => $filters,
            'staff' => $staff,
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
}
