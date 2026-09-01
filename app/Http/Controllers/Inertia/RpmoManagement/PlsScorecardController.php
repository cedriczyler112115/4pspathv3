<?php

namespace App\Http\Controllers\Inertia\RpmoManagement;

use App\Http\Controllers\Inertia\Verification\SemestralVerificationController;
use App\Models\ApplicationSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PlsScorecardController extends SemestralVerificationController
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $userLevelId = (int) ($user?->user_level_id ?? 0);
        $isSuperAdmin = ($user?->id === 3 && $userLevelId === 0);
        $canScorecard = (int) ($user?->can_scorecard ?? 0) === 1;

        if (! $isSuperAdmin && ! $canScorecard) {
            $hasRoleAccess = DB::table('sidebar_menu_items')
                ->where('is_active', 1)
                ->where(function ($q) {
                    $q->where('label', 'like', '%RPMO%')
                        ->orWhere('href', 'like', '%rpmo-management%')
                        ->orWhere('key', 'like', '%rpmo-management%');
                })
                ->get()
                ->some(function ($item) use ($userLevelId) {
                    $levels = array_filter(array_map('intval', json_decode($item->user_levels ?? '[]', true) ?: []));

                    return empty($levels) || ($userLevelId > 0 && in_array($userLevelId, $levels, true));
                });

            if (! $hasRoleAccess) {
                abort(403, __('Unauthorized access to RPMO Management.'));
            }
        }

        $filters = [
            'search' => (string) $request->string('search'),
            'year' => $request->has('year') ? (string) $request->string('year') : ApplicationSetting::defaultYear(),
            'semester' => $request->has('semester') ? (string) $request->string('semester') : ApplicationSetting::defaultSemester(),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $query = DB::table('users')
            ->leftJoin('ipc_semester', 'ipc_semester.user_id', '=', 'users.id')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->leftJoin('user_level', 'users.user_level_id', '=', 'user_level.level_id')
            ->where('users.is_pl', 1);

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

        if ($filters['year'] !== '') {
            $query->where('ipc_semester.year', $filters['year']);
        }

        if ($filters['semester'] !== '') {
            $query->where('ipc_semester.semester', $filters['semester']);
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

        $dbYears = DB::table('ipc_semester')
            ->join('users', 'ipc_semester.user_id', '=', 'users.id')
            ->where('users.is_pl', 1)
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

        return Inertia::render('RpmoManagement/PlsScorecard', [
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

    public function showPlRating(int $ratingId): Response
    {
        $response = parent::show($ratingId);
        $ref = new \ReflectionProperty($response, 'props');
        $props = $ref->getValue($response);

        $scorecardData = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stii', 'stii.sem_target_id', '=', 'sti.id')
            ->where('sti.semester_id', $ratingId)
            ->select([
                'stii.id as item_id',
                'stii.scorecard_quantity_score',
                'stii.scorecard_quality_score',
                'stii.scorecard_timeliness_score',
                'stii.scorecard_remarks',
                'stii.scorecard_created',
            ])
            ->get()
            ->keyBy('item_id');

        if (isset($props['indicators']) && is_array($props['indicators'])) {
            foreach ($props['indicators'] as &$group) {
                if (isset($group['items']) && is_array($group['items'])) {
                    foreach ($group['items'] as &$item) {
                        $itemId = $item['itemId'] ?? null;
                        if ($itemId && isset($scorecardData[$itemId])) {
                            $sc = $scorecardData[$itemId];
                            $item['scorecardEfficiency'] = $sc->scorecard_quantity_score !== null ? (string) $sc->scorecard_quantity_score : null;
                            $item['scorecardQuality'] = $sc->scorecard_quality_score !== null ? (string) $sc->scorecard_quality_score : null;
                            $item['scorecardTimeliness'] = $sc->scorecard_timeliness_score !== null ? (string) $sc->scorecard_timeliness_score : null;
                            $item['scorecardRemarks'] = (string) ($sc->scorecard_remarks ?? '');
                            $item['scorecardCreated'] = $sc->scorecard_created;
                        }
                    }
                }
            }
        }

        return Inertia::render('RpmoManagement/PlRating', $props);
    }

    public function updateAccomplishment(Request $request, int $ratingId, int $itemId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $item = DB::table('ipc_sem_targets_indicator_itemlist as stii')
            ->join('ipc_sem_targets_indicator as sti', 'stii.sem_target_id', '=', 'sti.id')
            ->where('stii.id', $itemId)
            ->where('sti.semester_id', $ratingId)
            ->select(['stii.id'])
            ->first();

        abort_if($item === null, 404);

        $validated = $request->validate([
            'actualAccomplishment' => ['nullable', 'string'],
            'actQuality' => ['nullable'],
            'actEfficiency' => ['nullable'],
            'actTimeliness' => ['nullable'],
            'movs' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $parseScoreStr = function ($val) {
            if ($val === null || (string) $val === '') {
                return null;
            }
            $str = trim((string) $val);
            if (strtoupper($str) === 'N/A') {
                return 'N/A';
            }
            $n = (float) $str;
            return ($n >= 1 && $n <= 5) ? (string) $str : null;
        };

        $updateData = [
            'scorecard_created' => $userId,
        ];

        if (array_key_exists('actEfficiency', $validated)) {
            $updateData['scorecard_quantity_score'] = $parseScoreStr($validated['actEfficiency']);
        }
        if (array_key_exists('actQuality', $validated)) {
            $updateData['scorecard_quality_score'] = $parseScoreStr($validated['actQuality']);
        }
        if (array_key_exists('actTimeliness', $validated)) {
            $updateData['scorecard_timeliness_score'] = $parseScoreStr($validated['actTimeliness']);
        }
        if (array_key_exists('remarks', $validated)) {
            $updateData['scorecard_remarks'] = (string) ($validated['remarks'] ?? '');
        }

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->update($updateData);

        return back()->with('success', __('Scorecard ratings and remarks updated successfully.'));
    }
}
