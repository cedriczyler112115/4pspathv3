<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function index(Request $request): Response
    {
        $supervisorId = (int) Auth::id();

        $filters = [
            'search' => (string) $request->string('search'),
            'year' => $request->has('year') ? (string) $request->string('year') : ApplicationSetting::defaultYear(),
            'semester' => $request->has('semester') ? (string) $request->string('semester') : ApplicationSetting::defaultSemester(),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $query = DB::table('users')
            ->leftJoin('ipc_semester', function ($join) use ($filters): void {
                $join->on('ipc_semester.user_id', '=', 'users.id');

                if ($filters['year'] !== '') {
                    $join->where('ipc_semester.year', $filters['year']);
                }

                if ($filters['semester'] !== '') {
                    $join->where('ipc_semester.semester', $filters['semester']);
                }
            })
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->leftJoin('user_level', 'users.user_level_id', '=', 'user_level.level_id')
            ->where('users.supervisor_id', $supervisorId)
            ->where('users.is_status', 1);

        if ($filters['search'] !== '') {
            $like = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($like): void {
                $q->whereRaw("CONCAT_WS(' ', users.last_name, users.first_name, users.middle_name, users.extension_name) LIKE ?", [$like])
                    ->orWhere('users.last_name', 'like', $like)
                    ->orWhere('users.first_name', 'like', $like)
                    ->orWhere('users.middle_name', 'like', $like)
                    ->orWhere('users.email', 'like', $like)
                    ->orWhere('users.position', 'like', $like)
                    ->orWhere('users.designation', 'like', $like);
            });
        }

        $records = $query->select([
            'users.id as user_id',
            'users.last_name',
            'users.first_name',
            'users.middle_name',
            'users.extension_name',
            'users.email',
            'users.contact_number',
            'users.position',
            'users.designation',
            'users.avatar',
            'users.division_id',
            'users.section_id',
            DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
            DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
            'user_level.level_name as user_level_name',
            'users.is_status as user_status',
            'ipc_semester.id as semester_id',
            'ipc_semester.year',
            'ipc_semester.semester',
            'ipc_semester.lock',
            'ipc_semester.final_rating',
            'ipc_semester.adjectival_rating',
            'ipc_semester.overall_remarks',
            'ipc_semester.is_ready',
            'ipc_semester.date_ready',
            'ipc_semester.date_verified',
            'ipc_semester.date_created as semester_date_created',
        ])
            ->orderByRaw('ipc_semester.date_ready IS NULL')
            ->orderBy('ipc_semester.date_ready')
            ->orderByRaw('ipc_semester.lock IS NULL')
            ->orderByDesc('ipc_semester.lock')
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->orderByDesc('ipc_semester.year')
            ->orderBy('ipc_semester.semester')
            ->paginate($filters['perPage'])
            ->withQueryString()
            ->through(fn ($r) => [
                'userId' => (int) $r->user_id,
                'lastName' => (string) ($r->last_name ?? ''),
                'firstName' => (string) ($r->first_name ?? ''),
                'middleName' => (string) ($r->middle_name ?? ''),
                'extensionName' => (string) ($r->extension_name ?? ''),
                'fullName' => trim(collect([$r->last_name, $r->first_name, $r->middle_name, $r->extension_name])->filter()->join(' ')),
                'email' => (string) ($r->email ?? ''),
                'contactNumber' => $r->contact_number,
                'position' => $r->position,
                'designation' => $r->designation,
                'avatar' => $r->avatar ?? null,
                'avatarUrl' => ! empty($r->avatar)
                    ? (str_starts_with($r->avatar, 'http') ? $r->avatar : asset('storage/'.$r->avatar))
                    : null,
                'divisionName' => $r->division_name,
                'sectionName' => $r->section_name,
                'userLevelName' => $r->user_level_name,
                'userStatus' => (int) $r->user_status,
                'semesterId' => $r->semester_id ? (int) $r->semester_id : null,
                'year' => $r->year ? (string) $r->year : null,
                'semester' => $r->semester ? (string) $r->semester : null,
                'lock' => $r->lock !== null ? (int) $r->lock : null,
                'isReady' => (int) ($r->is_ready ?? 0),
                'dateReady' => $r->date_ready,
                'dateVerified' => $r->date_verified,
                'finalRating' => $r->final_rating !== null ? (float) $r->final_rating : null,
                'adjectivalRating' => $r->adjectival_rating,
                'overallRemarks' => $r->overall_remarks,
                'dateCreated' => $r->semester_date_created,
            ]);

        // Available years from ipc_semester for this supervisor's staff
        $dbYears = DB::table('ipc_semester')
            ->join('users', 'ipc_semester.user_id', '=', 'users.id')
            ->where('users.supervisor_id', $supervisorId)
            ->whereNotNull('ipc_semester.year')
            ->distinct()
            ->orderByDesc('ipc_semester.year')
            ->pluck('ipc_semester.year')
            ->map(fn ($y) => (string) $y)
            ->values()
            ->all();

        $currentYear = (int) date('Y');
        $defaultYears = array_map('strval', range($currentYear + 1, $currentYear - 3));
        $years = array_values(array_unique(array_merge($dbYears, $defaultYears)));
        rsort($years);

        return Inertia::render('Verification', [
            'filters' => $filters,
            'records' => $records,
            'years' => $years,
            'semesters' => [
                ['value' => '1', 'label' => '1st Semester'],
                ['value' => '2', 'label' => '2nd Semester'],
            ],
            'perPageOptions' => [
                ['value' => 10, 'label' => '10'],
                ['value' => 25, 'label' => '25'],
                ['value' => 50, 'label' => '50'],
                ['value' => 100, 'label' => '100'],
            ],
        ]);
    }
}
