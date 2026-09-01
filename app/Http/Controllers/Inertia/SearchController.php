<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Services\UserDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request, UserDirectory $directory): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'division' => (string) $request->string('division'),
            'section' => (string) $request->string('section'),
            'year' => $request->has('year') ? (string) $request->string('year') : ApplicationSetting::defaultYear(),
            'semester' => $request->has('semester') ? (string) $request->string('semester') : ApplicationSetting::defaultSemester(),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $users = $directory->search(
            $filters['search'],
            $filters['division'],
            $filters['section'],
            $filters['year'],
            $filters['semester'],
            $filters['perPage'],
        )->through(function ($u) {
            $avatarUrl = ! empty($u->avatar)
                ? (str_starts_with($u->avatar, 'http') ? $u->avatar : asset('storage/'.$u->avatar))
                : null;

            return [
                'id' => (int) $u->user_id,
                'userId' => (int) $u->user_id,
                'lastName' => (string) ($u->last_name ?? ''),
                'firstName' => (string) ($u->first_name ?? ''),
                'middleName' => (string) ($u->middle_name ?? ''),
                'extensionName' => (string) ($u->extension_name ?? ''),
                'fullName' => trim(collect([$u->last_name, $u->first_name, $u->middle_name, $u->extension_name])->filter()->join(' ')),
                'email' => (string) ($u->email ?? ''),
                'contactNumber' => $u->contact_number,
                'position' => $u->position,
                'designation' => $u->designation,
                'divisionName' => $u->division_name,
                'sectionName' => $u->section_name,
                'avatar' => $u->avatar,
                'avatarUrl' => $avatarUrl,
                'avatar_url' => $avatarUrl,
                'semesterId' => $u->semester_id ? (int) $u->semester_id : null,
                'year' => $u->year ? (string) $u->year : null,
                'semester' => $u->semester !== null ? (int) $u->semester : null,
                'finalRating' => $u->final_rating !== null ? (string) $u->final_rating : null,
                'adjectivalRating' => $u->adjectival_rating ?? null,
                'overallRemarks' => $u->overall_remarks ?? null,
                'lock' => $u->lock !== null ? (int) $u->lock : null,
                'isReady' => $u->is_ready !== null ? (int) $u->is_ready : null,
            ];
        });

        $dbYears = DB::table('ipc_semester')
            ->whereNotNull('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (string) $y)
            ->values()
            ->all();

        $currentYear = (int) date('Y');
        $defaultYears = array_map('strval', range($currentYear + 1, 2021));
        $years = array_values(array_unique(array_merge($dbYears, $defaultYears)));
        rsort($years);

        return Inertia::render('Search', [
            'filters' => $filters,
            'users' => $users,
            'divisions' => $directory->divisions(),
            'sections' => $directory->sections('', true),
            'years' => array_map(fn ($y) => ['value' => $y, 'label' => $y], $years),
            'semesters' => [
                ['value' => '1', 'label' => '1st Semester'],
                ['value' => '2', 'label' => '2nd Semester'],
            ],
        ]);
    }
}
