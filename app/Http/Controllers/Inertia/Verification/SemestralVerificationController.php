<?php

namespace App\Http\Controllers\Inertia\Verification;

use App\Http\Controllers\Inertia\RatingsController;
use App\Models\ApplicationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Inertia\Response;

class SemestralVerificationController extends RatingsController
{
    public function index(Request $request): Response
    {
        $ratingId = (int) ($request->integer('ratingId') ?: $request->integer('id'));
        if ($ratingId <= 0) {
            abort(404, __('Semestral rating ID is required for verification.'));
        }

        return $this->show($ratingId);
    }

    public function show(int $ratingId): Response
    {
        $authId = Auth::id();
        abort_if($authId === null, 403);

        $semRecord = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->first();

        abort_if($semRecord === null, 404);

        // Fetch ratee user profile
        $userProfile = DB::table('users')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->where('users.id', $semRecord->user_id)
            ->select([
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.position',
                'users.designation',
                'users.avatar',
                DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
            ])
            ->first();

        $fullName = $userProfile ? trim(($userProfile->last_name ?? '') . (filled($userProfile->last_name) ? ', ' : '') . collect([$userProfile->first_name, $userProfile->middle_name])->filter()->join(' ')) : '';

        // Query Semestral Targets and Itemlists
        $indicators = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stii', 'stii.sem_target_id', '=', 'sti.id')
            ->leftJoin('users as sc_user', 'stii.scorecard_created', '=', 'sc_user.id')
            ->where('sti.semester_id', $ratingId)
            ->select([
                'sti.id as indicator_id',
                'sti.kra_category',
                'sti.activity',
                'sti.display_order as indicator_order',
                'stii.id as item_id',
                'stii.new_semester',
                'stii.description',
                'stii.rg_quantity',
                'stii.rg_quality',
                'stii.rg_timeliness',
                'stii.rg_movs',
                'stii.rg_remarks',
                'stii.actual_accomp',
                'stii.quality_score',
                'stii.quantity_score',
                'stii.timeliness_score',
                'stii.average',
                'stii.verified',
                'stii.scorecard_quantity_score',
                'stii.scorecard_quality_score',
                'stii.scorecard_timeliness_score',
                'stii.scorecard_remarks',
                'stii.scorecard_created',
                DB::raw("TRIM(CONCAT_WS(' ', sc_user.first_name, sc_user.last_name, sc_user.extension_name)) as scorecard_created_by_name"),
            ])
            ->orderBy('sti.kra_category')
            ->orderBy('sti.display_order')
            ->orderBy('stii.id')
            ->get();

        $uploadDir = public_path('uploaded_movs');
        $attachmentCounts = [];
        if (File::isDirectory($uploadDir)) {
            foreach (File::files($uploadDir) as $file) {
                $filename = $file->getFilename();
                if (preg_match('/^(\d+)_/i', $filename, $matches)) {
                    $itmId = (int) $matches[1];
                    $attachmentCounts[$itmId] = ($attachmentCounts[$itmId] ?? 0) + 1;
                }
            }
        }

        $grouped = [];
        foreach ($indicators as $row) {
            $indId = $row->indicator_id;
            if (!isset($grouped[$indId])) {
                $grouped[$indId] = [
                    'indicatorId' => (int) $row->indicator_id,
                    'kraCategory' => (int) $row->kra_category,
                    'activity' => (string) $row->activity,
                    'items' => [],
                ];
            }
            $grouped[$indId]['items'][] = [
                'itemId' => (int) $row->item_id,
                'newSemester' => (int) $row->new_semester,
                'description' => (string) $row->description,
                'efficiencyTarget' => (string) ($row->rg_quantity ?? ''),
                'qualityTarget' => (string) ($row->rg_quality ?? ''),
                'timelinessTarget' => (string) ($row->rg_timeliness ?? ''),
                'movs' => (string) ($row->rg_movs ?? ''),
                'remarks' => (string) ($row->rg_remarks ?? ''),
                'actualAccomplishment' => (string) ($row->actual_accomp ?? ''),
                'actQuality' => $row->quality_score ? (float) $row->quality_score : null,
                'actEfficiency' => $row->quantity_score ? (float) $row->quantity_score : null,
                'actTimeliness' => $row->timeliness_score ? (float) $row->timeliness_score : null,
                'scorecardEfficiency' => $row->scorecard_quantity_score !== null ? (string) $row->scorecard_quantity_score : null,
                'scorecardQuality' => $row->scorecard_quality_score !== null ? (string) $row->scorecard_quality_score : null,
                'scorecardTimeliness' => $row->scorecard_timeliness_score !== null ? (string) $row->scorecard_timeliness_score : null,
                'scorecardRemarks' => (string) ($row->scorecard_remarks ?? ''),
                'scorecardCreated' => $row->scorecard_created ? (int) $row->scorecard_created : null,
                'scorecardCreatedByName' => $row->scorecard_created_by_name ?: null,
                'averageScore' => $row->average ? (float) $row->average : null,
                'attachmentCount' => $attachmentCounts[(int) $row->item_id] ?? 0,
                'hasAttachments' => ($attachmentCounts[(int) $row->item_id] ?? 0) > 0,
                'verified' => (int) ($row->verified ?? 0),
            ];
        }

        // Fetch Development Plan (Part II Areas of Improvement)
        $areasOfImprovement = DB::table('ipc_areas_improvement')
            ->where('semester_id', $ratingId)
            ->orderBy('id', 'desc')
            ->get([
                'id',
                'areas_improvement',
                'development_activities',
                'support_resources',
                'progress_intervention',
                'date_encoded',
            ]);

        // Re-calculate rating summary
        $this->recalculateSemesterRating($ratingId);

        $updatedRating = DB::table('ipc_semester')->where('id', $ratingId)->first();

        // Calculate Category Scores
        $includeStrategic = ApplicationSetting::boolean('include_strategic_function', true);
        $calcCatScore = function (int $catId) use ($ratingId): string {
            $avg = DB::table('ipc_sem_targets_indicator_itemlist as stil')
                ->join('ipc_sem_targets_indicator as sti', 'stil.sem_target_id', '=', 'sti.id')
                ->where('sti.semester_id', $ratingId)
                ->where('sti.kra_category', $catId)
                ->whereNotNull('stil.average')
                ->where('stil.average', '!=', '')
                ->where('stil.average', '>', 0)
                ->avg('stil.average');

            return $avg !== null ? number_format((float) $avg, 5, '.', '') : '0.00000';
        };

        $strategicScore = $includeStrategic ? $calcCatScore(1) : '0.00000';
        $coreScore = $calcCatScore(2);
        $supportScore = $calcCatScore(3);

        $allItemIds = [];
        foreach ($grouped as $g) {
            foreach ($g['items'] as $it) {
                $allItemIds[] = $it['itemId'];
            }
        }

        $historyRecords = DB::table('ipc_sem_target_edit_histories as h')
            ->where(function ($q) use ($grouped, $allItemIds): void {
                if (!empty($grouped)) {
                    $q->whereIn('h.sem_target_id', array_keys($grouped));
                }
                if (!empty($allItemIds)) {
                    $q->orWhereIn('h.sem_item_id', $allItemIds);
                }
            })
            ->select('h.sem_target_id', 'h.sem_item_id')
            ->get();

        $historyTargetIds = $historyRecords->pluck('sem_target_id')->map(fn ($id) => (int) $id)->unique()->values()->toArray();
        $historyItemIds = $historyRecords->pluck('sem_item_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->toArray();

        return Inertia::render('Verification/SemestralVerification', [
            'rating' => [
                'id' => $updatedRating->id,
                'year' => (string) $updatedRating->year,
                'semester' => (int) $updatedRating->semester,
                'finalRating' => $updatedRating->final_rating ?? '0.00000',
                'adjectivalRating' => $updatedRating->adjectival_rating ?? 'N/A',
                'lock' => (int) ($updatedRating->lock ?? 0),
                'isReady' => (int) ($updatedRating->is_ready ?? 0),
                'dateVerified' => $updatedRating->date_verified,
                'dateCreated' => $updatedRating->date_created,
                'overallRemarks' => $updatedRating->overall_remarks,
                'recommendation' => $updatedRating->recommendation ?? '',
                'strengths' => $updatedRating->strengths ?? '',
            ],
            'userProfile' => [
                'fullName' => $fullName,
                'position' => $userProfile->position ?? '',
                'designation' => $userProfile->designation ?? '',
                'divisionName' => $userProfile->division_name ?? '',
                'sectionName' => $userProfile->section_name ?? '',
                'avatar' => $userProfile->avatar ?? null,
                'avatarUrl' => ! empty($userProfile?->avatar)
                    ? (str_starts_with($userProfile->avatar, 'http') ? $userProfile->avatar : asset('storage/'.$userProfile->avatar))
                    : null,
            ],
            'functionScores' => [
                'strategicScore' => $strategicScore,
                'coreScore' => $coreScore,
                'supportScore' => $supportScore,
                'finalScore' => $updatedRating->final_rating ?? '0.00000',
                'adjectival' => $updatedRating->adjectival_rating ?? 'N/A',
            ],
            'includeStrategicFunction' => $includeStrategic,
            'indicators' => array_values($grouped),
            'historyTargetIds' => $historyTargetIds,
            'historyItemIds' => $historyItemIds,
            'areasOfImprovement' => $areasOfImprovement,
            'deletedTargets' => $this->getDeletedTargets($ratingId),
            'checkpointChanges' => $this->getCheckpointChanges($ratingId),
            'documentationFiles' => $this->getDocumentationFiles(),
            'isSupervisorVerification' => true,
        ]);
    }
}
