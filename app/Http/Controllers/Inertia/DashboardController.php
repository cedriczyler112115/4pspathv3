<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $selectedYear = $request->has('year')
            ? (string) $request->string('year')
            : ApplicationSetting::defaultYear();

        $selectedSemester = $request->has('semester')
            ? (string) $request->string('semester')
            : ApplicationSetting::defaultSemester();

        // 1. Total Active Users
        $totalActiveUsers = DB::table('users')
            ->where('is_status', 1)
            ->count();

        // 2. Staff Without Supervisor
        $staffWithoutSupervisor = DB::table('users')
            ->where('is_status', 1)
            ->where(function ($q) {
                $q->whereNull('supervisor_id')
                    ->orWhere('supervisor_id', 0);
            })
            ->count();

        // 3. Semestral Target / Assessment Records for Year & Semester
        $ipcQuery = DB::table('ipc_semester')
            ->join('users', 'ipc_semester.user_id', '=', 'users.id')
            ->where('users.is_status', 1)
            ->where('ipc_semester.year', $selectedYear)
            ->where('ipc_semester.semester', $selectedSemester);

        $forVerification = (clone $ipcQuery)->count();

        $readyForVerification = (clone $ipcQuery)
            ->where('ipc_semester.is_ready', 1)
            ->whereNull('ipc_semester.date_verified')
            ->count();

        $verifiedStaff = (clone $ipcQuery)
            ->whereNotNull('ipc_semester.date_verified')
            ->count();

        $notReadyForVerification = (clone $ipcQuery)
            ->where(function ($q) {
                $q->whereNull('ipc_semester.is_ready')
                    ->orWhere('ipc_semester.is_ready', 0);
            })
            ->whereNull('ipc_semester.date_verified')
            ->count();

        // 4. Division Breakdown Stats
        $divisions = DB::table('lib_division')->orderBy('division_name')->get();
        $divisionStats = [];
        foreach ($divisions as $d) {
            $userCount = DB::table('users')
                ->where('is_status', 1)
                ->where('division_id', $d->id)
                ->count();

            $ipcQuery = DB::table('ipc_semester')
                ->join('users', 'ipc_semester.user_id', '=', 'users.id')
                ->where('users.is_status', 1)
                ->where('users.division_id', $d->id)
                ->where('ipc_semester.year', $selectedYear)
                ->where('ipc_semester.semester', $selectedSemester);

            $forVerif = (clone $ipcQuery)->count();
            $ready = (clone $ipcQuery)->where('ipc_semester.is_ready', 1)->whereNull('ipc_semester.date_verified')->count();
            $verified = (clone $ipcQuery)->whereNotNull('ipc_semester.date_verified')->count();
            $notReady = (clone $ipcQuery)->where(function ($q) {
                $q->whereNull('ipc_semester.is_ready')->orWhere('ipc_semester.is_ready', 0);
            })->whereNull('ipc_semester.date_verified')->count();

            $divisionStats[] = [
                'id' => (int) $d->id,
                'name' => $d->division_name,
                'totalUsers' => $userCount,
                'forVerification' => $forVerif,
                'ready' => $ready,
                'verified' => $verified,
                'notReady' => $notReady,
                'completionRate' => $forVerif > 0 ? round(($verified / $forVerif) * 100, 1) : 0,
            ];
        }

        // Available years
        $dbYears = DB::table('ipc_semester')
            ->whereNotNull('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($y) => (string) $y)
            ->values()
            ->all();

        $currentYear = (int) date('Y');
        $defaultYears = array_map('strval', range($currentYear + 1, $currentYear - 3));
        $years = array_values(array_unique(array_merge($dbYears, $defaultYears)));
        rsort($years);

        return Inertia::render('Dashboard', [
            'appName' => config('app.name'),
            'user' => $user ? [
                'name' => $user->name,
                'email' => $user->email,
            ] : null,
            'filters' => [
                'year' => $selectedYear,
                'semester' => $selectedSemester,
            ],
            'years' => $years,
            'semesters' => [
                ['value' => '1', 'label' => '1st semester'],
                ['value' => '2', 'label' => '2nd semester'],
            ],
            'stats' => [
                'totalActiveUsers' => $totalActiveUsers,
                'readyForVerification' => $readyForVerification,
                'notReadyForVerification' => $notReadyForVerification,
                'verifiedStaff' => $verifiedStaff,
                'forVerification' => $forVerification,
                'staffWithoutSupervisor' => $staffWithoutSupervisor,
            ],
            'divisionStats' => $divisionStats,
            'lastUpdated' => now()->format('n/j/Y, g:i:s A'),
            'entryPoints' => [
                [
                    'label' => 'Annual Targets',
                    'href' => route('annualtarget.index'),
                    'description' => 'View and manage your annual performance target commitments.',
                ],
                [
                    'label' => 'Semestral Ratings',
                    'href' => route('myratings.index'),
                    'description' => 'Track your semestral performance, accomplishments, and ratings.',
                ],
                [
                    'label' => 'Verification',
                    'href' => route('verification'),
                    'description' => 'Review and verify staff semestral performance submissions.',
                ],
            ],
        ]);
    }
}

