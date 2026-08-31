<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Support\KraCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RatingsController extends Controller
{
    public function show(int $ratingId): Response
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $rating = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        abort_if($rating === null, 404);

        $userProfile = DB::table('users')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->where('users.id', $userId)
            ->select([
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.position',
                'users.designation',
                DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
            ])
            ->first();

        $fullName = $userProfile ? trim(($userProfile->last_name ?? '') . (filled($userProfile->last_name) ? ', ' : '') . collect([$userProfile->first_name, $userProfile->middle_name])->filter()->join(' ')) : '';

        $semRecord = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        abort_if($semRecord === null, 404);

        // Query Semestral Targets and Itemlists
        $indicators = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stii', 'stii.sem_target_id', '=', 'sti.id')
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
                'averageScore' => $row->average ? (float) $row->average : null,
                'attachmentCount' => $attachmentCounts[(int) $row->item_id] ?? 0,
                'hasAttachments' => ($attachmentCounts[(int) $row->item_id] ?? 0) > 0,
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
            ->where(function ($q) use ($grouped, $allItemIds) {
                if (!empty($grouped)) {
                    $q->whereIn('h.sem_target_id', array_keys($grouped));
                }
                if (!empty($allItemIds)) {
                    $q->orWhereIn('h.sem_item_id', $allItemIds);
                }
            })
            ->select('h.sem_target_id', 'h.sem_item_id')
            ->get();

        $historyTargetIds = $historyRecords->pluck('sem_target_id')->map(fn($id) => (int) $id)->unique()->values()->toArray();
        $historyItemIds = $historyRecords->pluck('sem_item_id')->filter()->map(fn($id) => (int) $id)->unique()->values()->toArray();

        return Inertia::render('Ratings/SemestralTarget', [
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
        ]);
    }

    public function storeDocumentation(Request $request, int $ratingId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $request->validate([
            'files' => ['required', 'array', 'min:1'],
            'files.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,gif,webp,bmp,svg,mp4,mov,avi,mkv,wmv,webm,m4v,ppt,pptx,doc,docx',
                'max:51200',
            ],
        ]);

        $destination = public_path('documentation');
        File::ensureDirectoryExists($destination);

        $files = $request->file('files', []);
        if (! is_array($files)) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $originalName) ?: 'document';
            $ext = strtolower($file->getClientOriginalExtension());
            $fileName = $safeName . '_' . Carbon::now('Asia/Manila')->format('YmdHis') . '_' . uniqid() . '.' . $ext;

            $file->move($destination, $fileName);
        }

        return back()->with('success', __('Documentation files uploaded successfully.'));
    }

    public function destroyDocumentation(Request $request, int $ratingId): RedirectResponse
    {
        $validated = $request->validate([
            'fileName' => ['required', 'string'],
        ]);

        $safeName = basename($validated['fileName']);
        $filePath = public_path('documentation/' . $safeName);

        if (\Illuminate\Support\Facades\File::exists($filePath)) {
            \Illuminate\Support\Facades\File::delete($filePath);
        }

        return back()->with('success', __('Documentation file deleted successfully.'));
    }

    private function getDeletedTargets(int $ratingId): array
    {
        $rating = DB::table('ipc_semester')->where('id', $ratingId)->first();
        if (!$rating) {
            return [];
        }

        $activeTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $ratingId)
            ->pluck('id')
            ->toArray();

        $userHistoryTargetIds = DB::table('ipc_sem_target_edit_histories')
            ->where('user_id', $rating->user_id)
            ->pluck('sem_target_id')
            ->unique()
            ->toArray();

        $allTargetIds = array_values(array_unique(array_merge($activeTargetIds, $userHistoryTargetIds)));

        if (empty($allTargetIds)) {
            return [];
        }

        $records = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('ipc_sem_targets_indicator as sti', 'h.sem_target_id', '=', 'sti.id')
            ->leftJoin('users as u', 'h.user_id', '=', 'u.id')
            ->whereIn('h.sem_target_id', $allTargetIds)
            ->where('h.field_name', 'deleted')
            ->select([
                'h.id',
                'h.sem_target_id',
                'h.sem_item_id',
                'h.justification',
                'h.date_created',
                'h.old_value',
                'h.original_value',
                'h.new_value',
                'u.first_name',
                'u.last_name',
                'sti.kra_category as active_kra_category',
                'sti.activity as active_activity',
            ])
            ->orderBy('h.id', 'desc')
            ->get();

        return $records->map(function ($row) {
            $semTargetId = (int) $row->sem_target_id;
            $semItemId = $row->sem_item_id ? (int) $row->sem_item_id : null;

            // KRA Category
            $kraCat = $row->active_kra_category;
            if (!$kraCat) {
                $catHist = DB::table('ipc_sem_target_edit_histories')
                    ->where('sem_target_id', $semTargetId)
                    ->where('field_name', 'kra_category')
                    ->first();
                $kraCat = $catHist ? (int) ($catHist->original_value ?: $catHist->old_value) : 2;
            }
            $catLabel = match ((int) $kraCat) {
                1 => 'Strategic Function',
                2 => 'Core Function',
                3 => 'Support Function',
                default => 'Core Function',
            };

            // Key Result Area (Activity)
            $activity = $row->active_activity;
            if (!$activity) {
                $actHist = DB::table('ipc_sem_target_edit_histories')
                    ->where('sem_target_id', $semTargetId)
                    ->where('field_name', 'activity')
                    ->first();
                $activity = $actHist ? ($actHist->original_value ?: $actHist->old_value) : null;
            }
            if (!$activity) {
                $activity = $semItemId ? 'Sub-Target Entry' : ($row->original_value ?: ($row->old_value ?: 'Deleted Target Entry'));
            }

            // Success Indicator (Description)
            $description = null;
            if ($semItemId) {
                $descHist = DB::table('ipc_sem_target_edit_histories')
                    ->where('sem_target_id', $semTargetId)
                    ->where('sem_item_id', $semItemId)
                    ->where('field_name', 'description')
                    ->first();
                $description = $descHist ? ($descHist->original_value ?: $descHist->old_value) : null;
                if (!$description) {
                    $description = $row->original_value ?: $row->old_value;
                }
            } else {
                $descHists = DB::table('ipc_sem_target_edit_histories')
                    ->where('sem_target_id', $semTargetId)
                    ->where('field_name', 'description')
                    ->get();
                $descs = [];
                foreach ($descHists as $dh) {
                    $v = trim((string) ($dh->original_value ?: $dh->old_value));
                    if ($v !== '' && $v !== 'For Deletion' && !in_array($v, $descs, true)) {
                        $descs[] = $v;
                    }
                }
                $description = !empty($descs) ? implode("\n\n", $descs) : ($row->original_value ?: $row->old_value);
            }

            $userName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));

            return [
                'id' => $row->id,
                'sem_target_id' => $semTargetId,
                'sem_item_id' => $semItemId,
                'kra_category_label' => $catLabel,
                'activity' => $activity,
                'description' => $description ?: '-',
                'deleted_at' => $row->date_created ? Carbon::parse($row->date_created)->format('M d, Y h:i A') : '-',
                'user_name' => $userName ?: 'System',
                'justification' => $row->justification ?: '-',
            ];
        })->all();
    }

    private function getCheckpointChanges(int $ratingId): array
    {
        $activeTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $ratingId)
            ->pluck('id')
            ->all();

        $allTargetIds = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('ipc_sem_targets_indicator as sti', 'h.sem_target_id', '=', 'sti.id')
            ->where(function ($q) use ($activeTargetIds, $ratingId) {
                if (! empty($activeTargetIds)) {
                    $q->whereIn('h.sem_target_id', $activeTargetIds);
                }
                $q->orWhere('sti.semester_id', $ratingId);
                $q->orWhere('h.field_name', 'deleted');
            })
            ->pluck('h.sem_target_id')
            ->unique()
            ->all();

        $histories = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('ipc_sem_targets_indicator as sti', 'h.sem_target_id', '=', 'sti.id')
            ->leftJoin('ipc_sem_targets_indicator_itemlist as stil', 'h.sem_item_id', '=', 'stil.id')
            ->whereIn('h.sem_target_id', $allTargetIds)
            ->select([
                'h.id',
                'h.sem_target_id',
                'h.sem_item_id',
                'h.field_name',
                'h.action_type',
                'h.original_value',
                'h.old_value',
                'h.new_value',
                'h.justification',
                'h.date_created',
                'sti.kra_category as current_kra_category',
                'sti.activity as current_activity',
                'stil.description as current_description',
                'stil.rg_quantity as current_quantity',
                'stil.rg_quality as current_quality',
                'stil.rg_timeliness as current_timeliness',
                'stil.rg_movs as current_movs',
                'stil.rg_remarks as current_remarks',
            ])
            ->orderBy('h.id', 'asc')
            ->get();

        $fieldLabels = [
            'activity' => 'Key Result Area',
            'description' => 'Success Indicator (Measure + Target)',
            'rg_quantity' => 'Efficiency',
            'rg_quality' => 'Quality',
            'rg_timeliness' => 'Timeliness',
            'rg_movs' => 'MOVs',
            'rg_remarks' => 'Remarks',
            'kra_category' => 'KRA Category',
            'created' => 'Target Creation',
            'deleted' => 'Target Deletion',
        ];

        $fieldOrder = [
            'activity' => 1,
            'description' => 2,
            'rg_quantity' => 3,
            'rg_quality' => 4,
            'rg_timeliness' => 5,
            'rg_movs' => 6,
            'rg_remarks' => 7,
            'kra_category' => 8,
            'created' => 9,
            'deleted' => 10,
        ];

        $groupedHistories = $histories->groupBy('sem_target_id');

        $checkpointRows = [];

        foreach ($groupedHistories as $semTargetId => $targetRecords) {
            $justifications = [];
            foreach ($targetRecords as $h) {
                if (filled($h->justification)) {
                    $justifications[] = $h->justification;
                }
            }

            $isTargetInDatabase = in_array((int) $semTargetId, $activeTargetIds, true);
            $hasTargetLevelDeletedRecord = $targetRecords->contains(function ($r) {
                return (empty($r->sem_item_id) || (int) $r->sem_item_id === 0)
                    && ($r->field_name === 'deleted' || $r->new_value === 'For Deletion');
            });

            $isDeletedTarget = (! $isTargetInDatabase) || $hasTargetLevelDeletedRecord;
            $isNewlyAdded = (! $isDeletedTarget) && $targetRecords->contains(fn ($r) => $r->field_name === 'created' && (empty($r->sem_item_id) || (int) $r->sem_item_id === 0));
            $firstRec = $targetRecords->first();

            $actRec = $targetRecords->firstWhere('field_name', 'activity');
            $activityTitle = (string) ($firstRec->current_activity ?: ($actRec ? ($actRec->old_value ?: ($actRec->original_value ?: 'Target Entry')) : 'Target Entry'));

            // 1. Target Level Fields (sem_item_id null or 0)
            $targetLevelRecords = $targetRecords->filter(fn ($r) => empty($r->sem_item_id) || (int) $r->sem_item_id === 0);
            $targetFields = [];

            foreach ($targetLevelRecords as $h) {
                $fieldName = (string) $h->field_name;
                if ($fieldName === 'deleted' || $fieldName === 'created') {
                    continue;
                }

                $label = $fieldLabels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
                $orderRank = $fieldOrder[$fieldName] ?? 99;

                $oldVal = $h->old_value ?: ($h->original_value ?: '-');
                $newVal = $isDeletedTarget ? 'For Deletion' : ($h->new_value ?: '-');

                if ($fieldName === 'kra_category') {
                    $oldVal = is_numeric($oldVal) ? KraCategory::label((int) $oldVal) : $oldVal;
                    $newVal = $isDeletedTarget ? 'For Deletion' : (is_numeric($newVal) ? KraCategory::label((int) $newVal) : $newVal);
                }

                $targetFields[] = [
                    'field_name' => $fieldName,
                    'field_label' => $label,
                    'order_rank' => $orderRank,
                    'old_value' => $oldVal,
                    'new_value' => $newVal,
                ];
            }

            $hasActivity = collect($targetFields)->contains(fn ($f) => $f['field_name'] === 'activity');
            if (! $hasActivity && filled($activityTitle)) {
                array_unshift($targetFields, [
                    'field_name' => 'activity',
                    'field_label' => 'Key Result Area',
                    'order_rank' => 1,
                    'old_value' => $activityTitle,
                    'new_value' => $isDeletedTarget ? 'For Deletion' : '-',
                ]);
            }

            usort($targetFields, fn ($a, $b) => $a['order_rank'] <=> $b['order_rank']);

            if ($isDeletedTarget) {
                $targetFields = array_values(array_filter($targetFields, fn ($f) => in_array($f['field_name'], ['kra_category', 'activity'], true)));
            }

            // 2. Sub-Target Item Groups (grouped by sem_item_id)
            $itemLevelRecords = $targetRecords->filter(fn ($r) => ! empty($r->sem_item_id) && (int) $r->sem_item_id > 0);
            $itemGroupsRaw = $itemLevelRecords->groupBy(fn ($r) => (int) $r->sem_item_id);

            $itemGroups = [];
            $itemCounter = 1;
            $totalSubItems = count($itemGroupsRaw);

            foreach ($itemGroupsRaw as $itemId => $iRecords) {
                $iFirstRec = $iRecords->first();
                $isSubItemDeleted = $isDeletedTarget || $iRecords->contains(function ($r) {
                    return $r->action_type === 'deleted'
                        || $r->new_value === 'For Deletion'
                        || ($r->field_name === 'deleted' && ! empty($r->sem_item_id));
                });
                $isSubItemCreated = (! $isSubItemDeleted) && $iRecords->contains(fn ($r) => $r->field_name === 'created' || $r->action_type === 'newly_added' || $r->action_type === 'added_sub_target');

                $iFields = [];

                if ($isSubItemCreated) {
                    $desc = $iFirstRec->current_description;
                    $createdRec = $iRecords->firstWhere('field_name', 'created');
                    if ($createdRec && filled($createdRec->new_value) && $createdRec->new_value !== 'Sub-target Added' && $createdRec->new_value !== 'Newly Added Target') {
                        $desc = $desc ?: $createdRec->new_value;
                    }

                    if (filled($desc)) {
                        $iFields[] = [
                            'field_name' => 'description',
                            'field_label' => 'Success Indicator (Measure + Target)',
                            'order_rank' => 2,
                            'old_value' => '-',
                            'new_value' => $desc,
                        ];
                    }

                    if (filled($iFirstRec->current_quantity)) {
                        $iFields[] = [
                            'field_name' => 'rg_quantity',
                            'field_label' => 'Efficiency',
                            'order_rank' => 3,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_quantity,
                        ];
                    }

                    if (filled($iFirstRec->current_quality)) {
                        $iFields[] = [
                            'field_name' => 'rg_quality',
                            'field_label' => 'Quality',
                            'order_rank' => 4,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_quality,
                        ];
                    }

                    if (filled($iFirstRec->current_timeliness)) {
                        $iFields[] = [
                            'field_name' => 'rg_timeliness',
                            'field_label' => 'Timeliness',
                            'order_rank' => 5,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_timeliness,
                        ];
                    }

                    if (filled($iFirstRec->current_movs)) {
                        $iFields[] = [
                            'field_name' => 'rg_movs',
                            'field_label' => 'MOVs',
                            'order_rank' => 6,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_movs,
                        ];
                    }

                    if (filled($iFirstRec->current_remarks)) {
                        $iFields[] = [
                            'field_name' => 'rg_remarks',
                            'field_label' => 'Remarks',
                            'order_rank' => 7,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_remarks,
                        ];
                    }
                } elseif ($isSubItemDeleted) {
                    $descRec = $iRecords->firstWhere('field_name', 'description');
                    $descOld = $descRec ? ($descRec->old_value ?: ($descRec->original_value ?: $iFirstRec->current_description)) : ($iFirstRec->current_description ?: '-');

                    $iFields[] = [
                        'field_name' => 'description',
                        'field_label' => 'Success Indicator (Measure + Target)',
                        'order_rank' => 2,
                        'old_value' => $descOld ?: '-',
                        'new_value' => 'For Deletion',
                    ];

                    $fieldNamesMap = [
                        'rg_quantity' => ['Efficiency', 3],
                        'rg_quality' => ['Quality', 4],
                        'rg_timeliness' => ['Timeliness', 5],
                        'rg_movs' => ['MOVs', 6],
                        'rg_remarks' => ['Remarks', 7],
                    ];

                    foreach ($fieldNamesMap as $fn => $meta) {
                        $rec = $iRecords->firstWhere('field_name', $fn);
                        $val = $rec ? ($rec->old_value ?: ($rec->original_value ?: null)) : null;
                        if (! filled($val)) {
                            $colName = 'current_' . str_replace('rg_', '', $fn);
                            $val = $iFirstRec->{$colName} ?? null;
                        }

                        if (filled($val)) {
                            $iFields[] = [
                                'field_name' => $fn,
                                'field_label' => $meta[0],
                                'order_rank' => $meta[1],
                                'old_value' => $val,
                                'new_value' => '-',
                            ];
                        }
                    }
                } else {
                    foreach ($iRecords as $h) {
                        $fieldName = (string) $h->field_name;
                        if ($fieldName === 'deleted' || $fieldName === 'created') {
                            continue;
                        }

                        $label = $fieldLabels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
                        $orderRank = $fieldOrder[$fieldName] ?? 99;

                        $oldVal = $h->old_value ?: ($h->original_value ?: '-');
                        $newVal = $h->new_value ?: '-';

                        $iFields[] = [
                            'field_name' => $fieldName,
                            'field_label' => $label,
                            'order_rank' => $orderRank,
                            'old_value' => $oldVal,
                            'new_value' => $newVal,
                        ];
                    }

                    usort($iFields, fn ($a, $b) => $a['order_rank'] <=> $b['order_rank']);
                }

                if ($totalSubItems > 1) {
                    if ($isSubItemDeleted) {
                        $itemLabel = '#' . $itemCounter . ' (Deleted Sub-Target)';
                    } elseif ($isSubItemCreated) {
                        $itemLabel = '#' . $itemCounter . ' (Newly Added Sub-Target)';
                    } else {
                        $itemLabel = '#' . $itemCounter;
                    }
                } else {
                    if ($isSubItemDeleted) {
                        $itemLabel = '(Deleted Sub-Target)';
                    } elseif ($isSubItemCreated) {
                        $itemLabel = '(Newly Added Sub-Target)';
                    } else {
                        $itemLabel = '';
                    }
                }

                $itemGroups[] = [
                    'item_id' => $itemId,
                    'item_label' => $itemLabel,
                    'is_created' => $isSubItemCreated,
                    'is_deleted' => $isSubItemDeleted,
                    'fields' => $iFields,
                ];

                $itemCounter++;
            }

            $justificationText = collect($justifications)->unique()->filter(fn ($j) => filled($j) && $j !== '-')->join('; ');

            $checkpointRows[] = [
                'sem_target_id' => $semTargetId,
                'activity_title' => $activityTitle,
                'is_new_target' => $isNewlyAdded && ! $isDeletedTarget,
                'is_deleted' => $isDeletedTarget,
                'target_fields' => $targetFields,
                'item_groups' => $itemGroups,
                'justification' => $justificationText ?: ($isDeletedTarget ? __('Target Deleted') : __('Target Entry / Update')),
            ];
        }

        return $checkpointRows;
    }

    private function getDocumentationFiles(): array
    {
        $dir = public_path('documentation');
        if (! File::exists($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(function ($file) {
                $name = $file->getFilename();
                $relativePath = 'documentation/' . $name;
                $mime = File::mimeType($file->getPathname()) ?: 'application/octet-stream';
                $ext = strtolower($file->getExtension());
                $isImage = str_starts_with($mime, 'image/') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'], true);
                $isPdf = $mime === 'application/pdf' || $ext === 'pdf';
                $isVideo = str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'wmv', 'webm', 'm4v'], true);
                $isPresentation = in_array($mime, [
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                ], true) || in_array($ext, ['ppt', 'pptx'], true);
                $isWord = in_array($mime, [
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ], true) || in_array($ext, ['doc', 'docx'], true);

                return [
                    'name' => $name,
                    'path' => $relativePath,
                    'url' => asset($relativePath),
                    'mime' => $mime,
                    'size' => $file->getSize(),
                    'modified_at' => Carbon::createFromTimestamp($file->getMTime())->setTimezone('Asia/Manila')->format('M d, Y h:i A'),
                    'type' => $isImage ? 'image' : ($isPdf ? 'pdf' : ($isVideo ? 'video' : ($isPresentation ? 'presentation' : ($isWord ? 'word' : 'other')))),
                ];
            })
            ->values()
            ->all();
    }

    public function updateAccomplishment(Request $request, int $ratingId, int $itemId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $sem = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        abort_if($sem === null, 404);
        if ((int) ($sem->lock ?? 0) >= 2 || ! empty($sem->date_verified)) {
            return back()->with('error', __('Ratings are locked and cannot be edited.'));
        }

        $item = DB::table('ipc_sem_targets_indicator_itemlist as stii')
            ->join('ipc_sem_targets_indicator as sti', 'stii.sem_target_id', '=', 'sti.id')
            ->where('stii.id', $itemId)
            ->where('sti.semester_id', $ratingId)
            ->select([
                'stii.id',
                'sti.id as sem_target_id',
                'stii.actual_accomp',
                'stii.quality_score',
                'stii.quantity_score',
                'stii.timeliness_score',
                'stii.average',
                'stii.rg_movs',
                'stii.rg_remarks',
            ])
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

        $parseScore = function ($val) {
            if ($val === null || $val === '' || strtoupper((string) $val) === 'N/A') {
                return null;
            }
            $n = (float) $val;
            return ($n >= 1 && $n <= 5) ? $n : null;
        };

        $qual = $parseScore($validated['actQuality'] ?? null);
        $eff = $parseScore($validated['actEfficiency'] ?? null);
        $time = $parseScore($validated['actTimeliness'] ?? null);

        $scores = array_filter([$qual, $eff, $time], fn ($v) => $v !== null && $v > 0);
        $average = !empty($scores) ? array_sum($scores) / count($scores) : null;

        $updateData = [];
        if (array_key_exists('actualAccomplishment', $validated)) {
            $updateData['actual_accomp'] = (string) ($validated['actualAccomplishment'] ?? '');
        }
        if (array_key_exists('actQuality', $validated)) {
            $updateData['quality_score'] = $qual;
        }
        if (array_key_exists('actEfficiency', $validated)) {
            $updateData['quantity_score'] = $eff;
        }
        if (array_key_exists('actTimeliness', $validated)) {
            $updateData['timeliness_score'] = $time;
        }
        if (!empty($scores) || array_key_exists('actQuality', $validated) || array_key_exists('actEfficiency', $validated) || array_key_exists('actTimeliness', $validated)) {
            $updateData['average'] = $average !== null ? number_format($average, 5, '.', '') : null;
        }
        if (array_key_exists('movs', $validated)) {
            $updateData['rg_movs'] = (string) ($validated['movs'] ?? '');
        }
        if (array_key_exists('remarks', $validated)) {
            $updateData['rg_remarks'] = (string) ($validated['remarks'] ?? '');
        }

        $hasChanges = false;
        foreach ($updateData as $key => $newVal) {
            $currVal = $item->{$key} ?? null;
            if ($newVal === null && $currVal === null) {
                continue;
            }
            if (is_numeric($newVal) && is_numeric($currVal)) {
                if ((float) $newVal !== (float) $currVal) {
                    $hasChanges = true;
                    break;
                }
            } else {
                if ((string) ($newVal ?? '') !== (string) ($currVal ?? '')) {
                    $hasChanges = true;
                    break;
                }
            }
        }

        if (!$hasChanges) {
            return back();
        }

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->update($updateData);

        $this->recalculateSemesterRating($ratingId);

        return back()->with('success', __('Accomplishment and ratings updated.'));
    }

    public function storeTarget(Request $request, int $ratingId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $sem = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        abort_if($sem === null, 404);

        $validated = $request->validate([
            'category' => ['required', 'integer', 'in:1,2,3'],
            'activity' => ['required', 'string'],
            'description' => ['required', 'string'],
            'efficiency' => ['required', 'string'],
            'quality' => ['required', 'string'],
            'timeliness' => ['required', 'string'],
            'movs' => ['required', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($userId, $ratingId, $validated): void {
            $now = Carbon::now('Asia/Manila');
            $maxOrder = DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $ratingId)
                ->where('kra_category', $validated['category'])
                ->max('display_order');

            $targetId = DB::table('ipc_sem_targets_indicator')->insertGetId([
                'ipc_target_indicator_id' => 0,
                'semester_id' => $ratingId,
                'kra_category' => $validated['category'],
                'display_order' => ((int) $maxOrder) + 1,
                'activity' => $validated['activity'],
                'target_status' => 1,
                'created_by' => $userId,
                'date_created' => $now,
                'modified_by' => $userId,
                'last_date_modified' => $now,
                'target_from' => $userId,
            ]);

            $itemId = DB::table('ipc_sem_targets_indicator_itemlist')->insertGetId([
                'target_orig_id' => 0,
                'sem_target_id' => $targetId,
                'display_order' => 1,
                'description' => $validated['description'],
                'rg_quantity' => $validated['efficiency'],
                'rg_quality' => $validated['quality'],
                'rg_timeliness' => $validated['timeliness'],
                'rg_movs' => $validated['movs'],
                'rg_remarks' => $validated['remarks'] ?? '',
                'created_by' => $userId,
                'date_created' => $now,
                'modified_by' => $userId,
                'date_modified' => $now,
            ]);

            // Log audit history entries
            $this->logFieldHistory($targetId, null, 'activity', '', $validated['activity'], $userId, $now, 'Target Created', 'newly_added');
            $this->logFieldHistory($targetId, null, 'kra_category', '', (string) $validated['category'], $userId, $now, 'Target Created', 'newly_added');

            $this->logFieldHistory($targetId, $itemId, 'description', '', $validated['description'], $userId, $now, 'Target Created', 'newly_added');
            $this->logFieldHistory($targetId, $itemId, 'rg_quantity', '', $validated['efficiency'], $userId, $now, 'Target Created', 'newly_added');
            $this->logFieldHistory($targetId, $itemId, 'rg_quality', '', $validated['quality'], $userId, $now, 'Target Created', 'newly_added');
            $this->logFieldHistory($targetId, $itemId, 'rg_timeliness', '', $validated['timeliness'], $userId, $now, 'Target Created', 'newly_added');
            $this->logFieldHistory($targetId, $itemId, 'rg_movs', '', $validated['movs'], $userId, $now, 'Target Created', 'newly_added');
            if (filled($validated['remarks'] ?? null)) {
                $this->logFieldHistory($targetId, $itemId, 'rg_remarks', '', $validated['remarks'], $userId, $now, 'Target Created', 'newly_added');
            }
        });

        return back()->with('success', __('Semestral target added.'));
    }

    public function storeSubTarget(Request $request, int $ratingId, int $targetId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $target = DB::table('ipc_sem_targets_indicator')
            ->where('id', $targetId)
            ->where('semester_id', $ratingId)
            ->first();

        abort_if($target === null, 404);

        $validated = $request->validate([
            'description' => ['required', 'string'],
            'efficiency' => ['nullable', 'string'],
            'quality' => ['nullable', 'string'],
            'timeliness' => ['nullable', 'string'],
            'movs' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        $now = Carbon::now('Asia/Manila');
        $maxOrder = (int) DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('sem_target_id', $targetId)
            ->max('display_order');

        DB::transaction(function () use ($userId, $targetId, $maxOrder, $validated, $now): void {
            $itemId = DB::table('ipc_sem_targets_indicator_itemlist')->insertGetId([
                'target_orig_id' => 0,
                'sem_target_id' => $targetId,
                'display_order' => $maxOrder + 1,
                'description' => $validated['description'],
                'rg_quantity' => $validated['efficiency'] ?? '',
                'rg_quality' => $validated['quality'] ?? '',
                'rg_timeliness' => $validated['timeliness'] ?? '',
                'rg_movs' => $validated['movs'] ?? '',
                'rg_remarks' => $validated['remarks'] ?? '',
                'created_by' => $userId,
                'date_created' => $now,
                'modified_by' => $userId,
                'date_modified' => $now,
            ]);

            // Log audit history entries
            $this->logFieldHistory($targetId, $itemId, 'description', '', $validated['description'], $userId, $now, 'Sub-Target Added', 'added_sub_target');
            $this->logFieldHistory($targetId, $itemId, 'rg_quantity', '', $validated['efficiency'] ?? '', $userId, $now, 'Sub-Target Added', 'added_sub_target');
            $this->logFieldHistory($targetId, $itemId, 'rg_quality', '', $validated['quality'] ?? '', $userId, $now, 'Sub-Target Added', 'added_sub_target');
            $this->logFieldHistory($targetId, $itemId, 'rg_timeliness', '', $validated['timeliness'] ?? '', $userId, $now, 'Sub-Target Added', 'added_sub_target');
            $this->logFieldHistory($targetId, $itemId, 'rg_movs', '', $validated['movs'] ?? '', $userId, $now, 'Sub-Target Added', 'added_sub_target');
            if (filled($validated['remarks'] ?? null)) {
                $this->logFieldHistory($targetId, $itemId, 'rg_remarks', '', $validated['remarks'], $userId, $now, 'Sub-Target Added', 'added_sub_target');
            }
        });

        return back()->with('success', __('Sub-target added successfully.'));
    }

    public function updateTargetGroup(Request $request, int $ratingId, int $targetId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $target = DB::table('ipc_sem_targets_indicator')
            ->where('id', $targetId)
            ->where('semester_id', $ratingId)
            ->first();

        abort_if($target === null, 404);

        $validated = $request->validate([
            'activity' => ['required', 'string'],
            'kraCategory' => ['required', 'integer', 'in:1,2,3'],
            'items' => ['required', 'array'],
            'items.*.itemId' => ['required', 'integer'],
            'items.*.description' => ['required', 'string'],
            'items.*.efficiencyTarget' => ['nullable', 'string'],
            'items.*.qualityTarget' => ['nullable', 'string'],
            'items.*.timelinessTarget' => ['nullable', 'string'],
            'items.*.movs' => ['nullable', 'string'],
            'items.*.remarks' => ['nullable', 'string'],
            'justification' => ['nullable', 'string'],
        ]);

        $justification = trim((string) ($validated['justification'] ?? 'Target Updated'));
        if (empty($justification)) {
            $justification = 'Target Updated';
        }

        $now = Carbon::now('Asia/Manila');

        DB::transaction(function () use ($userId, $targetId, $target, $validated, $justification, $now): void {
            // Log target level changes
            if ((string) $target->activity !== (string) $validated['activity']) {
                $this->logFieldHistory($targetId, null, 'activity', (string) $target->activity, $validated['activity'], $userId, $now, $justification, 'updated');
            }
            if ((int) $target->kra_category !== (int) $validated['kraCategory']) {
                $this->logFieldHistory($targetId, null, 'kra_category', (string) $target->kra_category, (string) $validated['kraCategory'], $userId, $now, $justification, 'updated');
            }

            DB::table('ipc_sem_targets_indicator')
                ->where('id', $targetId)
                ->update([
                    'activity' => $validated['activity'],
                    'kra_category' => $validated['kraCategory'],
                    'modified_by' => $userId,
                    'last_date_modified' => $now,
                ]);

            foreach ($validated['items'] as $itemData) {
                $itemId = (int) $itemData['itemId'];
                $existingItem = DB::table('ipc_sem_targets_indicator_itemlist')
                    ->where('id', $itemId)
                    ->where('sem_target_id', $targetId)
                    ->first();

                if (!$existingItem) {
                    continue;
                }

                $newDesc = (string) ($itemData['description'] ?? '');
                $newQty = (string) ($itemData['efficiencyTarget'] ?? '');
                $newQual = (string) ($itemData['qualityTarget'] ?? '');
                $newTime = (string) ($itemData['timelinessTarget'] ?? '');
                $newMovs = (string) ($itemData['movs'] ?? '');
                $newRem = (string) ($itemData['remarks'] ?? '');

                if ((string) $existingItem->description !== $newDesc) {
                    $this->logFieldHistory($targetId, $itemId, 'description', (string) $existingItem->description, $newDesc, $userId, $now, $justification, 'updated');
                }
                if ((string) $existingItem->rg_quantity !== $newQty) {
                    $this->logFieldHistory($targetId, $itemId, 'rg_quantity', (string) $existingItem->rg_quantity, $newQty, $userId, $now, $justification, 'updated');
                }
                if ((string) $existingItem->rg_quality !== $newQual) {
                    $this->logFieldHistory($targetId, $itemId, 'rg_quality', (string) $existingItem->rg_quality, $newQual, $userId, $now, $justification, 'updated');
                }
                if ((string) $existingItem->rg_timeliness !== $newTime) {
                    $this->logFieldHistory($targetId, $itemId, 'rg_timeliness', (string) $existingItem->rg_timeliness, $newTime, $userId, $now, $justification, 'updated');
                }
                if ((string) $existingItem->rg_movs !== $newMovs) {
                    $this->logFieldHistory($targetId, $itemId, 'rg_movs', (string) $existingItem->rg_movs, $newMovs, $userId, $now, $justification, 'updated');
                }
                if ((string) $existingItem->rg_remarks !== $newRem) {
                    $this->logFieldHistory($targetId, $itemId, 'rg_remarks', (string) $existingItem->rg_remarks, $newRem, $userId, $now, $justification, 'updated');
                }

                DB::table('ipc_sem_targets_indicator_itemlist')
                    ->where('id', $itemId)
                    ->where('sem_target_id', $targetId)
                    ->update([
                        'description' => $newDesc,
                        'rg_quantity' => $newQty,
                        'rg_quality' => $newQual,
                        'rg_timeliness' => $newTime,
                        'rg_movs' => $newMovs,
                        'rg_remarks' => $newRem,
                        'modified_by' => $userId,
                        'date_modified' => $now,
                    ]);
            }
        });

        return back()->with('success', __('Semestral target group updated successfully.'));
    }

    public function getEditHistory(Request $request, int $ratingId, int $targetId): JsonResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $itemId = $request->query('itemId');

        $itemIds = DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('sem_target_id', $targetId)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $rawRecords = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('users as u', 'u.id', '=', 'h.user_id')
            ->where(function ($q) use ($targetId, $itemIds, $itemId) {
                $q->where('h.sem_target_id', $targetId);
                if (!empty($itemIds)) {
                    $q->orWhereIn('h.sem_item_id', $itemIds);
                }
                if ($itemId) {
                    $q->orWhere('h.sem_item_id', (int) $itemId);
                }
            })
            ->select(
                'h.*',
                'u.name as user_name',
                'u.first_name',
                'u.last_name'
            )
            ->orderBy('h.id', 'asc')
            ->get();

        $fieldLabels = [
            'activity' => 'KEY RESULT AREA',
            'kra_category' => 'KRA CATEGORY',
            'description' => 'SUCCESS INDICATOR',
            'rg_quantity' => 'EFFICIENCY',
            'rg_quality' => 'QUALITY',
            'rg_timeliness' => 'TIMELINESS',
            'rg_movs' => 'MOVS',
            'rg_remarks' => 'REMARKS',
        ];

        $fieldOrder = [
            'activity' => 1,
            'kra_category' => 2,
            'description' => 3,
            'rg_quantity' => 4,
            'rg_quality' => 5,
            'rg_timeliness' => 6,
            'rg_movs' => 7,
            'rg_remarks' => 8,
        ];

        $kraRecords = [];
        $itemGroups = [];

        foreach ($rawRecords as $row) {
            $userName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            if (empty($userName)) {
                $userName = $row->user_name ?? 'System';
            }
            $fieldName = (string) ($row->field_name ?? '');
            $displayLabel = $fieldLabels[$fieldName] ?? strtoupper(str_replace('_', ' ', $fieldName));
            $orderRank = $fieldOrder[$fieldName] ?? 99;
            $semItemId = (int) ($row->sem_item_id ?? 0);

            $rec = [
                'id' => $row->id,
                'sem_target_id' => $row->sem_target_id,
                'sem_item_id' => $semItemId,
                'field_name' => $fieldName,
                'action_type' => $row->action_type ?? 'updated',
                'field_label' => $displayLabel,
                'order_rank' => $orderRank,
                'original_value' => $row->original_value,
                'old_value' => $row->old_value,
                'new_value' => $row->new_value,
                'justification' => $row->justification ?: '-',
                'user_name' => $userName,
                'date_created' => $row->date_created ? Carbon::parse($row->date_created)->format('M d, Y h:i A') : '-',
            ];

            if ($fieldName === 'activity' || $fieldName === 'kra_category' || $semItemId === 0) {
                $kraRecords[] = $rec;
            } else {
                $itemGroups[$semItemId][] = $rec;
            }
        }

        $targetActivity = DB::table('ipc_sem_targets_indicator')
            ->where('id', $targetId)
            ->value('activity') ?: 'Key Result Area';
        $kraTitleLimit = Str::limit($targetActivity, 75, '...');

        $allSections = [];

        if (!empty($kraRecords)) {
            $allSections[] = [
                'type' => 'kra',
                'title' => 'Key Result Area',
                'records' => $kraRecords,
            ];
        }

        $subCounter = 1;
        foreach ($itemGroups as $semItemId => $gRecords) {
            $title = 'SUB TARGET #' . $subCounter . ' OF ' . strtoupper($kraTitleLimit);
            $allSections[] = [
                'type' => 'item',
                'title' => $title,
                'records' => $gRecords,
            ];
            $subCounter++;
        }

        $processedRecords = [];

        foreach ($allSections as $sec) {
            $sRecords = $sec['records'];
            usort($sRecords, fn($a, $b) => $a['order_rank'] <=> $b['order_rank']);

            if ($sec['type'] !== 'kra') {
                $processedRecords[] = [
                    'is_separator' => true,
                    'separator_title' => $sec['title'],
                    'justification' => $sRecords[0]['justification'] ?? '-',
                    'justification_rowspan' => 0,
                    'date_created' => $sRecords[0]['date_created'] ?? '-',
                    'user_name' => $sRecords[0]['user_name'] ?? 'System',
                ];
            }

            foreach ($sRecords as $item) {
                $item['is_separator'] = false;
                $item['justification_rowspan'] = 0;
                $processedRecords[] = $item;
            }
        }

        $totalRowCount = count($processedRecords);
        if ($totalRowCount > 0) {
            $uniqueJustifications = collect($processedRecords)
                ->pluck('justification')
                ->filter(fn($j) => filled($j) && $j !== '-')
                ->unique()
                ->values();

            $combinedJustification = $uniqueJustifications->isNotEmpty()
                ? $uniqueJustifications->join("\n\n")
                : '-';

            $processedRecords[0]['justification_rowspan'] = $totalRowCount;
            $processedRecords[0]['justification'] = $combinedJustification;
            for ($i = 1; $i < $totalRowCount; $i++) {
                $processedRecords[$i]['justification_rowspan'] = 0;
            }
        }

        return response()->json([
            'records' => $processedRecords,
            'targetActivity' => $targetActivity,
        ]);
    }

    public function discardEditHistory(Request $request, int $ratingId, int $targetId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $itemId = $request->query('itemId');

        DB::transaction(function () use ($ratingId, $targetId, $itemId, $userId): void {
            $query = DB::table('ipc_sem_target_edit_histories');

            if ($itemId && (int) $itemId > 0) {
                $query->where(function ($q) use ($itemId, $targetId) {
                    $q->where('sem_item_id', (int) $itemId);
                    if ($targetId > 0) {
                        $q->orWhere(function ($q2) use ($targetId) {
                            $q2->where('sem_target_id', $targetId)
                                ->where('field_name', 'activity');
                        });
                    }
                });
            } elseif ($targetId && $targetId > 0) {
                $query->where('sem_target_id', $targetId);
            }

            $histories = $query->orderBy('id', 'asc')->get();

            if ($histories->isEmpty()) {
                return;
            }

            $nowManila = Carbon::now('Asia/Manila');
            $hasDeletedEvent = $histories->contains('field_name', 'deleted');

            if ($hasDeletedEvent) {
                if (!$targetId && $histories->first()->sem_target_id) {
                    $targetId = (int) $histories->first()->sem_target_id;
                }

                if ($targetId > 0) {
                    $targetExists = DB::table('ipc_sem_targets_indicator')->where('id', $targetId)->exists();

                    if (!$targetExists) {
                        $actRec = $histories->firstWhere('field_name', 'activity');
                        $kraRec = $histories->firstWhere('field_name', 'kra_category');

                        $activity = $actRec ? ($actRec->old_value ?: ($actRec->original_value ?: 'Restored Target')) : 'Restored Target';
                        $kraCategory = $kraRec ? (int) ($kraRec->old_value ?: ($kraRec->original_value ?: 1)) : 1;

                        $maxOrder = DB::table('ipc_sem_targets_indicator')
                            ->where('semester_id', $ratingId)
                            ->where('kra_category', $kraCategory)
                            ->max('display_order');

                        DB::table('ipc_sem_targets_indicator')->insert([
                            'id' => $targetId,
                            'ipc_target_indicator_id' => 0,
                            'semester_id' => $ratingId,
                            'kra_category' => $kraCategory,
                            'display_order' => ((int) $maxOrder) + 1,
                            'activity' => $activity,
                            'target_status' => 1,
                            'created_by' => $userId,
                            'date_created' => $nowManila,
                            'modified_by' => $userId,
                            'last_date_modified' => $nowManila,
                            'target_from' => $userId,
                        ]);
                    }

                    $itemGrouped = $histories->where('sem_item_id', '>', 0)->groupBy('sem_item_id');

                    if ($itemGrouped->isNotEmpty()) {
                        $itemDisplayOrder = 1;
                        foreach ($itemGrouped as $iId => $iRecords) {
                            $itemExists = DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $iId)->exists();
                            if ($itemExists) {
                                continue;
                            }

                            $descRec = $iRecords->firstWhere('field_name', 'description');
                            $qtyRec = $iRecords->firstWhere('field_name', 'rg_quantity');
                            $qualRec = $iRecords->firstWhere('field_name', 'rg_quality');
                            $timeRec = $iRecords->firstWhere('field_name', 'rg_timeliness');
                            $movsRec = $iRecords->firstWhere('field_name', 'rg_movs');
                            $remRec = $iRecords->firstWhere('field_name', 'rg_remarks');

                            DB::table('ipc_sem_targets_indicator_itemlist')->insert([
                                'id' => (int) $iId,
                                'target_orig_id' => 0,
                                'sem_target_id' => $targetId,
                                'display_order' => $itemDisplayOrder++,
                                'sem_item_id' => $ratingId,
                                'description' => $descRec ? ($descRec->old_value ?: ($descRec->original_value ?: 'Restored Sub-Target')) : 'Restored Sub-Target',
                                'rg_quantity' => $qtyRec ? ($qtyRec->old_value ?: $qtyRec->original_value) : null,
                                'rg_quality' => $qualRec ? ($qualRec->old_value ?: $qualRec->original_value) : null,
                                'rg_timeliness' => $timeRec ? ($timeRec->old_value ?: $timeRec->original_value) : null,
                                'rg_movs' => $movsRec ? ($movsRec->old_value ?: $movsRec->original_value) : null,
                                'rg_remarks' => $remRec ? ($remRec->old_value ?: $remRec->original_value) : null,
                                'remarks' => 1,
                                'created_by' => $userId,
                                'date_created' => $nowManila,
                                'modified_by' => $userId,
                                'date_modified' => $nowManila,
                            ]);
                        }
                    }
                }
            } else {
                $indicatorUpdates = [];
                $itemlistUpdates = [];

                foreach ($histories as $h) {
                    $origValue = $h->original_value !== null ? $h->original_value : $h->old_value;

                    if ($origValue === null) {
                        continue;
                    }

                    $tId = $h->sem_target_id;
                    $iId = $h->sem_item_id;

                    if ($h->field_name === 'activity') {
                        $indicatorUpdates[$tId]['activity'] = $origValue;
                    } elseif ($h->field_name === 'kra_category') {
                        $indicatorUpdates[$tId]['kra_category'] = (int) $origValue;
                    } elseif (in_array($h->field_name, ['description', 'rg_quantity', 'rg_quality', 'rg_timeliness', 'rg_movs', 'rg_remarks'], true) && $iId) {
                        $itemlistUpdates[$iId][$h->field_name] = $origValue;
                    }
                }

                foreach ($indicatorUpdates as $tId => $data) {
                    DB::table('ipc_sem_targets_indicator')
                        ->where('id', $tId)
                        ->update($data);
                }

                foreach ($itemlistUpdates as $iId => $data) {
                    DB::table('ipc_sem_targets_indicator_itemlist')
                        ->where('id', $iId)
                        ->update($data);
                }
            }

            $deleteQuery = DB::table('ipc_sem_target_edit_histories');
            if ($itemId && (int) $itemId > 0) {
                $deleteQuery->where(function ($q) use ($itemId, $targetId) {
                    $q->where('sem_item_id', (int) $itemId);
                    if ($targetId > 0) {
                        $q->orWhere(function ($q2) use ($targetId) {
                            $q2->where('sem_target_id', $targetId)
                                ->where('field_name', 'activity');
                        });
                    }
                });
            } else {
                $deleteQuery->where('sem_target_id', $targetId);
            }
            $deleteQuery->delete();
        });

        $this->recalculateSemesterRating($ratingId);

        return back()->with('success', __('Edit history discarded and values reverted to original successfully.'));
    }

    public function restoreDeletedTarget(Request $request, int $ratingId, int $targetId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $itemId = $request->input('itemId') ?: $request->query('itemId');

        DB::transaction(function () use ($ratingId, $targetId, $itemId, $userId): void {
            $nowManila = Carbon::now('Asia/Manila');

            // 1. Check if parent target exists in database
            $targetExists = DB::table('ipc_sem_targets_indicator')->where('id', $targetId)->where('semester_id', $ratingId)->exists();

            if (! $targetExists) {
                $targetHistories = DB::table('ipc_sem_target_edit_histories')
                    ->where('sem_target_id', $targetId)
                    ->where(fn ($q) => $q->whereNull('sem_item_id')->orWhere('sem_item_id', 0))
                    ->get();

                $actRec = $targetHistories->firstWhere('field_name', 'activity');
                $kraRec = $targetHistories->firstWhere('field_name', 'kra_category');
                $dispRec = $targetHistories->firstWhere('field_name', 'display_order');

                $activity = $actRec ? ($actRec->old_value ?: ($actRec->original_value ?: 'Restored Target')) : 'Restored Target';
                $kraCategory = $kraRec ? (int) ($kraRec->old_value ?: ($kraRec->original_value ?: 2)) : 2;
                $displayOrder = $dispRec ? (int) ($dispRec->old_value ?: ($dispRec->original_value ?: 1)) : null;

                if (! $displayOrder) {
                    $maxOrder = DB::table('ipc_sem_targets_indicator')
                        ->where('semester_id', $ratingId)
                        ->where('kra_category', $kraCategory)
                        ->max('display_order');
                    $displayOrder = ((int) $maxOrder) + 1;
                }

                DB::table('ipc_sem_targets_indicator')->insert([
                    'id' => $targetId,
                    'ipc_target_indicator_id' => 0,
                    'semester_id' => $ratingId,
                    'kra_category' => $kraCategory,
                    'display_order' => $displayOrder,
                    'activity' => $activity,
                    'target_status' => 1,
                    'created_by' => $userId,
                    'date_created' => $nowManila,
                    'modified_by' => $userId,
                    'last_date_modified' => $nowManila,
                    'target_from' => $userId,
                ]);
            }

            // 2. Restore Sub-Target items
            $itemQuery = DB::table('ipc_sem_target_edit_histories')->where('sem_target_id', $targetId);
            if ($itemId && (int) $itemId > 0) {
                $itemQuery->where('sem_item_id', (int) $itemId);
            } else {
                $itemQuery->where('sem_item_id', '>', 0);
            }

            $itemHistories = $itemQuery->get()->groupBy('sem_item_id');

            foreach ($itemHistories as $iId => $iRecords) {
                $itemExists = DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $iId)->exists();
                if ($itemExists) {
                    continue;
                }

                $descRec = $iRecords->firstWhere('field_name', 'description');
                $qtyRec = $iRecords->firstWhere('field_name', 'rg_quantity');
                $qualRec = $iRecords->firstWhere('field_name', 'rg_quality');
                $timeRec = $iRecords->firstWhere('field_name', 'rg_timeliness');
                $movsRec = $iRecords->firstWhere('field_name', 'rg_movs');
                $remRec = $iRecords->firstWhere('field_name', 'rg_remarks');
                $dispRec = $iRecords->firstWhere('field_name', 'display_order');

                $itemDisplayOrder = $dispRec ? (int) ($dispRec->old_value ?: ($dispRec->original_value ?: 1)) : null;
                if (! $itemDisplayOrder) {
                    $maxItemOrder = DB::table('ipc_sem_targets_indicator_itemlist')
                        ->where('sem_target_id', $targetId)
                        ->max('display_order');
                    $itemDisplayOrder = ((int) $maxItemOrder) + 1;
                }

                DB::table('ipc_sem_targets_indicator_itemlist')->insert([
                    'id' => (int) $iId,
                    'target_orig_id' => 0,
                    'sem_target_id' => $targetId,
                    'display_order' => $itemDisplayOrder,
                    'sem_item_id' => $ratingId,
                    'description' => $descRec ? ($descRec->old_value ?: ($descRec->original_value ?: 'Restored Sub-Target')) : 'Restored Sub-Target',
                    'rg_quantity' => $qtyRec ? ($qtyRec->old_value ?: $qtyRec->original_value) : null,
                    'rg_quality' => $qualRec ? ($qualRec->old_value ?: $qualRec->original_value) : null,
                    'rg_timeliness' => $timeRec ? ($timeRec->old_value ?: $timeRec->original_value) : null,
                    'rg_movs' => $movsRec ? ($movsRec->old_value ?: $movsRec->original_value) : null,
                    'rg_remarks' => $remRec ? ($remRec->old_value ?: $remRec->original_value) : null,
                    'remarks' => 1,
                    'created_by' => $userId,
                    'date_created' => $nowManila,
                    'modified_by' => $userId,
                    'date_modified' => $nowManila,
                ]);
            }

            // 3. Clean up history entries
            $deleteQuery = DB::table('ipc_sem_target_edit_histories');
            if ($itemId && (int) $itemId > 0) {
                $deleteQuery->where(function ($q) use ($itemId, $targetId) {
                    $q->where('sem_item_id', (int) $itemId);
                    if ($targetId > 0) {
                        $q->orWhere(function ($q2) use ($targetId) {
                            $q2->where('sem_target_id', $targetId)
                                ->where('field_name', 'activity');
                        });
                    }
                });
            } else {
                $deleteQuery->where('sem_target_id', $targetId);
            }
            $deleteQuery->delete();
        });

        $this->recalculateSemesterRating($ratingId);

        return back()->with('success', __('Target restored back to original location successfully.'));
    }

    public function destroyTarget(Request $request, int $ratingId, int $targetId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $justification = trim((string) $request->input('justification', 'Target Deleted'));
        if (empty($justification)) {
            $justification = 'Target Deleted';
        }

        $target = DB::table('ipc_sem_targets_indicator')
            ->where('id', $targetId)
            ->where('semester_id', $ratingId)
            ->first();

        if ($target) {
            $items = DB::table('ipc_sem_targets_indicator_itemlist')->where('sem_target_id', $targetId)->get();
            $nowManila = Carbon::now('Asia/Manila');

            $histories = DB::table('ipc_sem_target_edit_histories')
                ->where('sem_target_id', $targetId)
                ->get();

            // Determine if the target is newly added (even if updated)
            $isNewlyAddedTarget = ((int) ($target->ipc_target_indicator_id ?? 0) === 0)
                || $histories->contains(fn ($h) => $h->action_type === 'newly_added')
                || ($histories->isNotEmpty() && $histories->every(fn ($h) => in_array($h->action_type, ['newly_added', 'added_sub_target'], true)));

            DB::transaction(function () use ($ratingId, $targetId, $target, $items, $userId, $justification, $nowManila, $isNewlyAddedTarget): void {
                if ($isNewlyAddedTarget) {
                    // Purge all history entries for newly added target
                    DB::table('ipc_sem_target_edit_histories')
                        ->where('sem_target_id', $targetId)
                        ->delete();
                } else {
                    // Log target level fields
                    $this->logFieldHistory($targetId, null, 'activity', $target->activity ?? '', 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    $this->logFieldHistory($targetId, null, 'kra_category', (string) $target->kra_category, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    $this->logFieldHistory($targetId, null, 'display_order', (string) ($target->display_order ?? 1), 'For Deletion', $userId, $nowManila, $justification, 'deleted');

                    // Log item level fields
                    foreach ($items as $item) {
                        $itemId = (int) $item->id;
                        $this->logFieldHistory($targetId, $itemId, 'description', $item->description ?? '', 'For Deletion', $userId, $nowManila, $justification, 'deleted');

                        if (filled($item->rg_quantity)) {
                            $this->logFieldHistory($targetId, $itemId, 'rg_quantity', $item->rg_quantity, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                        }
                        if (filled($item->rg_quality)) {
                            $this->logFieldHistory($targetId, $itemId, 'rg_quality', $item->rg_quality, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                        }
                        if (filled($item->rg_timeliness)) {
                            $this->logFieldHistory($targetId, $itemId, 'rg_timeliness', $item->rg_timeliness, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                        }
                        if (filled($item->rg_movs)) {
                            $this->logFieldHistory($targetId, $itemId, 'rg_movs', $item->rg_movs, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                        }
                        if (filled($item->rg_remarks)) {
                            $this->logFieldHistory($targetId, $itemId, 'rg_remarks', $item->rg_remarks, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                        }
                        $this->logFieldHistory($targetId, $itemId, 'display_order', (string) ($item->display_order ?? 1), 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    }

                    // Log overall deleted entry
                    $this->logFieldHistory($targetId, null, 'deleted', $target->activity ?? 'Deleted Target', 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                }

                DB::table('ipc_sem_targets_indicator_itemlist')
                    ->where('sem_target_id', $targetId)
                    ->delete();

                DB::table('ipc_sem_targets_indicator')
                    ->where('id', $targetId)
                    ->where('semester_id', $ratingId)
                    ->delete();
            });

            $this->recalculateSemesterRating($ratingId);
        }

        return back()->with('success', __('Semestral target deleted and archived in history.'));
    }

    public function destroySubTarget(Request $request, int $ratingId, int $itemId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $justification = trim((string) $request->input('justification', 'Sub-Target Deleted'));
        if (empty($justification)) {
            $justification = 'Sub-Target Deleted';
        }

        $item = DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->first();

        if ($item) {
            $nowManila = Carbon::now('Asia/Manila');
            $semTargetId = (int) $item->sem_target_id;

            $itemHistories = DB::table('ipc_sem_target_edit_histories')
                ->where('sem_target_id', $semTargetId)
                ->where('sem_item_id', $itemId)
                ->get();

            // Determine if the sub-target item is newly added (even if updated)
            $isNewlyAddedSubTarget = ((int) ($item->target_orig_id ?? 0) === 0)
                || $itemHistories->contains(fn ($h) => in_array($h->action_type, ['newly_added', 'added_sub_target'], true))
                || ($itemHistories->isNotEmpty() && $itemHistories->every(fn ($h) => in_array($h->action_type, ['newly_added', 'added_sub_target'], true)));

            DB::transaction(function () use ($ratingId, $itemId, $semTargetId, $item, $userId, $justification, $nowManila, $isNewlyAddedSubTarget): void {
                if ($isNewlyAddedSubTarget) {
                    // Purge all history entries for newly added sub-target
                    DB::table('ipc_sem_target_edit_histories')
                        ->where('sem_target_id', $semTargetId)
                        ->where('sem_item_id', $itemId)
                        ->delete();
                } else {
                    $this->logFieldHistory($semTargetId, $itemId, 'description', $item->description ?? '', 'For Deletion', $userId, $nowManila, $justification, 'deleted');

                    if (filled($item->rg_quantity)) {
                        $this->logFieldHistory($semTargetId, $itemId, 'rg_quantity', $item->rg_quantity, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    }
                    if (filled($item->rg_quality)) {
                        $this->logFieldHistory($semTargetId, $itemId, 'rg_quality', $item->rg_quality, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    }
                    if (filled($item->rg_timeliness)) {
                        $this->logFieldHistory($semTargetId, $itemId, 'rg_timeliness', $item->rg_timeliness, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    }
                    if (filled($item->rg_movs)) {
                        $this->logFieldHistory($semTargetId, $itemId, 'rg_movs', $item->rg_movs, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    }
                    if (filled($item->rg_remarks)) {
                        $this->logFieldHistory($semTargetId, $itemId, 'rg_remarks', $item->rg_remarks, 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                    }
                    $this->logFieldHistory($semTargetId, $itemId, 'display_order', (string) ($item->display_order ?? 1), 'For Deletion', $userId, $nowManila, $justification, 'deleted');

                    // Log overall deleted entry for sub-target
                    $this->logFieldHistory($semTargetId, $itemId, 'deleted', $item->description ?? 'Sub-Target Deleted', 'For Deletion', $userId, $nowManila, $justification, 'deleted');
                }

                DB::table('ipc_sem_targets_indicator_itemlist')
                    ->where('id', $itemId)
                    ->delete();
            });

            $this->recalculateSemesterRating($ratingId);
        }

        return back()->with('success', __('Sub-target deleted successfully.'));
    }

    public function toggleStatus(Request $request, int $ratingId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $sem = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        abort_if($sem === null, 404);

        $action = $request->input('action'); // 'lock', 'ready', 'unlock'

        if ($action === 'lock') {
            DB::table('ipc_semester')
                ->where('id', $ratingId)
                ->update([
                    'lock' => 1,
                    'is_ready' => 1,
                    'date_ready' => Carbon::now('Asia/Manila'),
                ]);
            return back()->with('success', __('Semestral target saved and locked successfully.'));
        } elseif ($action === 'ready') {
            DB::table('ipc_semester')
                ->where('id', $ratingId)
                ->update([
                    'lock' => 2,
                    'is_ready' => 1,
                    'date_ready' => Carbon::now('Asia/Manila'),
                ]);
            return back()->with('success', __("You have indicated that you are ready! Your semestral targets are now locked from further editing."));
        } elseif ($action === 'unready' || $action === 'cancel_ready') {
            DB::table('ipc_semester')
                ->where('id', $ratingId)
                ->update([
                    'lock' => 1,
                    'is_ready' => 1,
                ]);
            return back()->with('success', __('Verification status cancelled. Accomplishments and scores are now editable (lock = 1).'));
        } elseif ($action === 'unlock') {
            DB::table('ipc_semester')
                ->where('id', $ratingId)
                ->update([
                    'lock' => 0,
                    'is_ready' => 0,
                ]);
            return back()->with('success', __('Semestral target unlocked successfully.'));
        }

        return back();
    }

    public function storeAreaOfImprovement(Request $request, int $ratingId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $validated = $request->validate([
            'areas_improvement' => ['required', 'string'],
            'development_activities' => ['required', 'string'],
            'support_resources' => ['required', 'string'],
            'progress_intervention' => ['nullable', 'string'],
        ]);

        DB::table('ipc_areas_improvement')->insert([
            'semester_id' => $ratingId,
            'areas_improvement' => $validated['areas_improvement'],
            'development_activities' => $validated['development_activities'],
            'support_resources' => $validated['support_resources'],
            'progress_intervention' => $validated['progress_intervention'] ?? '',
            'encoded_by' => $userId,
            'date_encoded' => now(),
        ]);

        return back()->with('success', __('Area of Improvement added.'));
    }

    public function destroyAreaOfImprovement(int $ratingId, int $id): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        DB::table('ipc_areas_improvement')
            ->where('id', $id)
            ->where('semester_id', $ratingId)
            ->delete();

        return back()->with('success', __('Area of Improvement removed.'));
    }

    public function reorderTargets(Request $request, int $ratingId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $order = $request->input('order', []); // array of { indicatorId, displayOrder }
        if (is_array($order)) {
            foreach ($order as $item) {
                if (isset($item['indicatorId'], $item['displayOrder'])) {
                    DB::table('ipc_sem_targets_indicator')
                        ->where('id', (int) $item['indicatorId'])
                        ->where('semester_id', $ratingId)
                        ->update(['display_order' => (int) $item['displayOrder']]);
                }
            }
        }

        return back()->with('success', __('Targets reordered successfully.'));
    }


    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'year' => (string) $request->string('year'),
            'semester' => (string) $request->string('semester'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $userId = Auth::id();

        $profile = null;
        if ($userId !== null) {
            $profile = DB::table('users')
                ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
                ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
                ->where('users.id', $userId)
                ->select([
                    'users.first_name',
                    'users.middle_name',
                    'users.last_name',
                    'users.position',
                    'users.designation',
                    DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                    DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
                ])
                ->first();
        }

        $query = DB::table('ipc_semester')
            ->where('user_id', $userId);

        if (filled($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('year', 'like', $search)
                    ->orWhere('final_rating', 'like', $search)
                    ->orWhere('adjectival_rating', 'like', $search)
                    ->orWhere('overall_remarks', 'like', $search);
            });
        }

        if (filled($filters['year'])) {
            $query->where('year', $filters['year']);
        }

        if (filled($filters['semester'])) {
            $query->where('semester', $filters['semester']);
        }

        $ratings = $query
            ->orderBy('year', 'desc')
            ->orderBy('semester', 'asc')
            ->paginate($filters['perPage']);

        return Inertia::render('Ratings/MyRatings', [
            'filters' => $filters,
            'profile' => $profile ? [
                'fullName' => trim(($profile->last_name ?? '') . (filled($profile->last_name) ? ', ' : '') . collect([$profile->first_name, $profile->middle_name])->filter()->join(' ')),
                'position' => (string) ($profile->position ?? ''),
                'designation' => (string) ($profile->designation ?? ''),
                'divisionName' => (string) ($profile->division_name ?? ''),
                'sectionName' => (string) ($profile->section_name ?? ''),
            ] : null,
            'years' => $this->years(),
            'semesters' => [
                ['value' => '1', 'label' => __('1st Semester')],
                ['value' => '2', 'label' => __('2nd Semester')],
            ],
            'perPageOptions' => [
                ['value' => 10, 'label' => '10'],
                ['value' => 25, 'label' => '25'],
                ['value' => 50, 'label' => '50'],
                ['value' => 100, 'label' => '100'],
            ],
            'ratings' => $ratings->through(fn ($rating) => [
                'id' => $rating->id,
                'year' => $rating->year,
                'semester' => (int) $rating->semester,
                'finalRating' => $rating->final_rating,
                'adjectivalRating' => $rating->adjectival_rating,
                'lock' => (int) ($rating->lock ?? 0),
                'dateVerified' => $rating->date_verified,
                'dateCreated' => $rating->date_created,
                'overallRemarks' => $rating->overall_remarks,
            ]),
        ]);
    }

    public function destroy(int $ratingId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $rating = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        abort_if($rating === null, 404);

        if ((int) ($rating->is_ready ?? 0) === 1 || filled($rating->date_verified) || (int) ($rating->lock ?? 0) === 2) {
            return back()->with('error', __('Cannot remove rating record because it is waiting for verification or verified.'));
        }

        DB::transaction(function () use ($rating, $userId): void {
            $semTargetIds = DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $rating->id)
                ->pluck('id')
                ->all();

            if (! empty($semTargetIds)) {
                DB::table('ipc_sem_targets_indicator_itemlist')
                    ->whereIn('sem_target_id', $semTargetIds)
                    ->delete();

                DB::table('ipc_sem_targets_indicator')
                    ->where('semester_id', $rating->id)
                    ->delete();
            }

            DB::table('ipc_semester')
                ->where('id', $rating->id)
                ->where('user_id', $userId)
                ->delete();
        });

        return back()->with('success', __('Semester rating record removed successfully.'));
    }

    public function years(): array
    {
        $userId = Auth::id();

        if ($userId === null) {
            return [];
        }

        return DB::table('ipc_semester')
            ->where('user_id', $userId)
            ->whereNotNull('year')
            ->select('year as target_year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->get()
            ->map(fn ($row) => (object) ['target_year' => (string) $row->target_year])
            ->all();
    }

    protected function logFieldHistory(
        int $semTargetId,
        ?int $semItemId,
        string $fieldName,
        ?string $oldVal,
        ?string $newVal,
        int $userId,
        Carbon $now,
        ?string $justification = null,
        string $actionType = 'updated'
    ): void {
        $oldValStr = (string) ($oldVal ?? '');
        $newValStr = (string) ($newVal ?? '');

        if ($oldValStr === $newValStr && $newValStr !== 'For Deletion') {
            return;
        }

        $query = DB::table('ipc_sem_target_edit_histories')
            ->where('sem_target_id', $semTargetId)
            ->where('field_name', $fieldName);

        if ($semItemId !== null && $semItemId > 0) {
            $query->where('sem_item_id', $semItemId);
        } else {
            $query->whereNull('sem_item_id');
        }

        $existingHistory = (clone $query)->first();

        if ($existingHistory !== null) {
            $targetActionType = $existingHistory->action_type;
            if (in_array($existingHistory->action_type, ['newly_added', 'added_sub_target'], true) && $actionType !== 'deleted') {
                $targetActionType = $existingHistory->action_type;
            } else {
                $targetActionType = $actionType;
            }

            $updateData = [
                'action_type' => $targetActionType,
                'old_value' => $oldValStr,
                'new_value' => $newValStr,
                'last_edited_value' => $oldValStr,
                'user_id' => $userId,
                'date_created' => $now,
                'updated_at' => $now,
            ];
            if (filled($justification)) {
                $updateData['justification'] = $justification;
            }
            $query->update($updateData);
        } else {
            DB::table('ipc_sem_target_edit_histories')->insert([
                'sem_target_id' => $semTargetId,
                'sem_item_id' => ($semItemId !== null && $semItemId > 0) ? $semItemId : null,
                'field_name' => $fieldName,
                'action_type' => $actionType,
                'original_value' => $oldValStr,
                'old_value' => $oldValStr,
                'new_value' => $newValStr,
                'last_edited_value' => $oldValStr,
                'justification' => $justification,
                'user_id' => $userId,
                'date_created' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function recalculateSemesterRating(int $ratingId): void
    {
        $includeStrategic = ApplicationSetting::boolean('include_strategic_function', true);

        $calcCatAvg = function (int $catId) use ($ratingId): float {
            $avg = DB::table('ipc_sem_targets_indicator_itemlist as stil')
                ->join('ipc_sem_targets_indicator as sti', 'stil.sem_target_id', '=', 'sti.id')
                ->where('sti.semester_id', $ratingId)
                ->where('sti.kra_category', $catId)
                ->whereNotNull('stil.average')
                ->where('stil.average', '!=', '')
                ->where('stil.average', '>', 0)
                ->avg('stil.average');

            return $avg !== null ? (float) $avg : 0.0;
        };

        $coreAvg = $calcCatAvg(2);
        $supportAvg = $calcCatAvg(3);

        if ($includeStrategic) {
            $strategicAvg = $calcCatAvg(1);
            $finalVal = ($strategicAvg + $coreAvg + $supportAvg) / 3.0;
        } else {
            $finalVal = ($coreAvg + $supportAvg) / 2.0;
        }

        $finalStr = number_format($finalVal, 5, '.', '');
        $calcFinal = (float) $finalStr;

        $adjectival = match (true) {
            $calcFinal >= 5.00 => 'Outstanding',
            $calcFinal >= 4.00 => 'Very Satisfactory',
            $calcFinal >= 3.00 => 'Satisfactory',
            $calcFinal >= 2.00 => 'Unsatisfactory',
            $calcFinal > 0.00 => 'Poor',
            default => 'N/A',
        };

        DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->update([
                'final_rating' => $finalStr,
                'adjectival_rating' => $adjectival,
            ]);
    }

    public function getItemAttachments(Request $request, int $ratingId, int $itemId): JsonResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $owned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $itemId)
            ->where('sem.id', $ratingId)
            ->where('sem.user_id', $userId)
            ->exists();

        if (! $owned) {
            return response()->json([], 403);
        }

        $attachments = $this->getAttachmentsListForItem($itemId);

        return response()->json($attachments);
    }

    public function uploadItemAttachments(Request $request, int $ratingId, int $itemId): JsonResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $sem = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        if ($sem === null || (int) ($sem->lock ?? 0) >= 2 || ! empty($sem->date_verified)) {
            return response()->json(['error' => __('Ratings are locked. Attachments cannot be uploaded.')], 403);
        }

        $owned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->where('itl.id', $itemId)
            ->where('sti.semester_id', $ratingId)
            ->exists();

        if (! $owned) {
            return response()->json(['error' => __('Unable to upload attachments for this target.')], 403);
        }

        $request->validate([
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'max:10240'],
        ], [
            'files.required' => __('Please choose at least one file to upload.'),
            'files.*.required' => __('Invalid file uploaded.'),
            'files.*.max' => __('Each file must be 10MB or smaller.'),
        ]);

        $files = $request->file('files', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }
        if (empty($files) && $request->hasFile('file')) {
            $single = $request->file('file');
            if ($single instanceof UploadedFile) {
                $files = [$single];
            }
        }

        if (empty($files)) {
            return response()->json(['error' => __('No files were received by the server. Please check file size.')], 422);
        }

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'jfif', 'webp'];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $ext = strtolower((string) $file->getClientOriginalExtension());
                if (! in_array($ext, $allowedExtensions, true)) {
                    return response()->json([
                        'error' => __('Only JPG, PNG, WEBP, JFIF, and PDF files are allowed.'),
                    ], 422);
                }
            }
        }

        $now = Carbon::now('Asia/Manila');
        $uploadDir = public_path('uploaded_movs');
        File::ensureDirectoryExists($uploadDir);

        $storedPaths = [];

        try {
            foreach ($files as $index => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $extension = strtolower((string) $file->getClientOriginalExtension());
                $baseName = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeBase = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $baseName) ?: 'mov';
                $safeBase = trim(substr($safeBase, 0, 80), '_-') ?: 'mov';
                $fileName = $itemId . '_' . $safeBase . '_' . $now->format('YmdHis') . '_' . ($index + 1) . '.' . $extension;
                $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                try {
                    $file->move($uploadDir, $fileName);
                } catch (\Throwable $moveEx) {
                    $temporaryPath = $file->getRealPath();
                    if ($temporaryPath === false || ! File::copy($temporaryPath, $destinationPath)) {
                        throw new \RuntimeException('Unable to save the uploaded MOV file: ' . $moveEx->getMessage());
                    }
                }

                $storedPaths[] = $destinationPath;
            }

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $itemId)
                ->update([
                    'has_attachments' => 1,
                    'modified_by' => $userId,
                    'date_modified' => $now,
                ]);
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                File::delete($storedPath);
            }

            report($exception);

            return response()->json(['error' => __('The attachments could not be saved: ') . $exception->getMessage()], 500);
        }

        $attachments = $this->getAttachmentsListForItem($itemId);

        return response()->json($attachments);
    }

    public function deleteItemAttachment(Request $request, int $ratingId, int $itemId): JsonResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $filename = (string) $request->input('filename', '');

        $sem = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        if ($sem === null || (int) ($sem->lock ?? 0) >= 2 || ! empty($sem->date_verified)) {
            return response()->json(['error' => __('Ratings are locked. Attachments cannot be deleted.')], 403);
        }

        $owned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->where('itl.id', $itemId)
            ->where('sti.semester_id', $ratingId)
            ->exists();

        if (! $owned || empty($filename)) {
            return response()->json(['error' => __('Unable to delete attachment.')], 403);
        }

        $pattern = '/^' . preg_quote((string) $itemId, '/') . '_/i';
        if (preg_match($pattern, $filename) !== 1) {
            return response()->json(['error' => __('Invalid attachment file specified.')], 422);
        }

        $uploadDir = public_path('uploaded_movs');
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $now = Carbon::now('Asia/Manila');
        $remainingAttachments = $this->getAttachmentsListForItem($itemId);
        $hasAttachments = count($remainingAttachments) > 0 ? 1 : null;

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->update([
                'has_attachments' => $hasAttachments,
                'modified_by' => $userId,
                'date_modified' => $now,
            ]);

        return response()->json($remainingAttachments);
    }

    public function getStaffMovSources(Request $request, int $ratingId, int $itemId): JsonResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $itemContext = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $itemId)
            ->where('sem.id', $ratingId)
            ->select(['sem.year', 'sem.semester'])
            ->first();

        $contextYear = (string) ($itemContext->year ?? now()->year);
        $contextSemester = (string) ($itemContext->semester ?? (now()->month >= 7 ? 2 : 1));

        $staffUsers = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->join('users as u', 'sem.user_id', '=', 'u.id')
            ->where('sem.user_id', '!=', $userId)
            ->select(['u.id', 'u.first_name', 'u.middle_name', 'u.last_name', 'u.position'])
            ->distinct()
            ->orderBy('u.last_name')
            ->orderBy('u.first_name')
            ->get()
            ->map(function (object $u): array {
                $fullName = mb_strtoupper(trim(($u->last_name ?? '') . (filled($u->last_name) ? ', ' : '') . collect([$u->first_name, $u->middle_name])->filter()->join(' ')), 'UTF-8');
                return [
                    'id' => $u->id,
                    'name' => $fullName,
                    'position' => $u->position,
                ];
            })
            ->values()
            ->all();

        $selectedStaffId = $request->input('staffUserId');
        $search = trim((string) $request->input('search', ''));

        $sources = [];
        if ($selectedStaffId) {
            $query = DB::table('ipc_sem_targets_indicator as sti')
                ->join('ipc_sem_targets_indicator_itemlist as itl', 'itl.sem_target_id', '=', 'sti.id')
                ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
                ->join('users as u', 'sem.user_id', '=', 'u.id')
                ->where('sem.user_id', (int) $selectedStaffId)
                ->where('itl.has_attachments', 1);

            if ($contextYear !== '') {
                $query->where('sem.year', $contextYear);
            }
            if ($contextSemester !== '') {
                $query->where('sem.semester', (int) $contextSemester);
            }

            if ($search !== '') {
                $searchTerm = '%' . $search . '%';
                $query->where(function ($q) use ($searchTerm): void {
                    $q->where('sti.activity', 'like', $searchTerm)
                        ->orWhere('itl.description', 'like', $searchTerm);
                });
            }

            $rawTargets = $query->select([
                'sti.id as sem_target_id',
                'sti.activity',
                'sti.kra_category',
                'itl.id as item_id',
                'itl.description',
            ])->get();

            $grouped = $rawTargets->groupBy('sem_target_id');
            foreach ($grouped as $targetId => $items) {
                $first = $items->first();
                $itemRows = [];
                foreach ($items as $item) {
                    $itemAttachments = $this->getAttachmentsListForItem((int) $item->item_id);
                    if (count($itemAttachments) > 0) {
                        $itemRows[] = [
                            'itemId' => (int) $item->item_id,
                            'description' => $item->description,
                            'attachmentCount' => count($itemAttachments),
                            'attachments' => $itemAttachments,
                        ];
                    }
                }
                if (! empty($itemRows)) {
                    $sources[] = [
                        'targetId' => (int) $targetId,
                        'activity' => $first->activity,
                        'kraCategory' => (int) $first->kraCategory,
                        'items' => $itemRows,
                    ];
                }
            }
        }

        return response()->json([
            'users' => $staffUsers,
            'sources' => $sources,
            'contextYear' => $contextYear,
            'contextSemester' => $contextSemester,
        ]);
    }

    public function copyStaffMovsToItem(Request $request, int $ratingId, int $destItemId): JsonResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $sourceItemId = (int) $request->input('sourceItemId', 0);
        if ($sourceItemId <= 0 || $destItemId <= 0) {
            return response()->json(['error' => __('Invalid target item.')], 422);
        }

        $sem = DB::table('ipc_semester')
            ->where('id', $ratingId)
            ->where('user_id', $userId)
            ->first();

        if ($sem === null || (int) ($sem->lock ?? 0) >= 2 || ! empty($sem->date_verified)) {
            return response()->json(['error' => __('Ratings are locked. Attachments cannot be copied.')], 403);
        }

        $destOwned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->where('itl.id', $destItemId)
            ->where('sti.semester_id', $ratingId)
            ->exists();

        $sourceOwned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $sourceItemId)
            ->where('sem.user_id', '!=', $userId)
            ->exists();

        if (! $destOwned || ! $sourceOwned) {
            return response()->json(['error' => __('Unable to copy MOVs from the selected staff target.')], 403);
        }

        $sourceAttachments = $this->getAttachmentsListForItem($sourceItemId);
        if (empty($sourceAttachments)) {
            return response()->json(['error' => __('No MOVs found for selected source target.')], 422);
        }

        $now = Carbon::now('Asia/Manila');
        $uploadDir = public_path('uploaded_movs');
        File::ensureDirectoryExists($uploadDir);

        $storedPaths = [];

        try {
            foreach ($sourceAttachments as $index => $attachment) {
                $sourcePath = public_path($attachment['path']);
                if (! File::exists($sourcePath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
                $baseName = pathinfo($attachment['name'], PATHINFO_FILENAME);
                $safeBase = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $baseName) ?: 'mov';
                $safeBase = trim(substr($safeBase, 0, 80), '_-') ?: 'mov';
                $fileName = $destItemId . '_' . $safeBase . '_' . $now->format('YmdHis') . '_' . ($index + 1) . '.' . $extension;
                $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

                if (! File::copy($sourcePath, $destinationPath)) {
                    throw new \RuntimeException('Unable to copy the MOV attachment file.');
                }

                $storedPaths[] = $destinationPath;
            }

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $destItemId)
                ->update([
                    'has_attachments' => 1,
                    'modified_by' => $userId,
                    'date_modified' => $now,
                ]);
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                File::delete($storedPath);
            }
            report($exception);
            return response()->json(['error' => __('The MOVs could not be copied. Please try again.')], 500);
        }

        $attachments = $this->getAttachmentsListForItem($destItemId);

        return response()->json($attachments);
    }

    public function copyStaffMovs(Request $request, int $ratingId): JsonResponse
    {
        $targetItemId = (int) $request->input('targetItemId', 0);
        return $this->copyStaffMovsToItem($request, $ratingId, $targetItemId);
    }

    private function getAttachmentsListForItem(int $itemId): array
    {
        $uploadDir = public_path('uploaded_movs');
        if (! File::isDirectory($uploadDir)) {
            return [];
        }

        $pattern = '/^' . preg_quote((string) $itemId, '/') . '_/i';

        return collect(File::files($uploadDir))
            ->filter(fn ($file): bool => preg_match($pattern, $file->getFilename()) === 1)
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->map(function ($file): array {
                $extension = strtolower($file->getExtension());
                $displayName = $file->getFilename();

                return [
                    'name' => $displayName,
                    'path' => 'uploaded_movs/' . $file->getFilename(),
                    'filename' => $file->getFilename(),
                    'url' => asset('uploaded_movs/' . $file->getFilename()),
                    'type' => $extension === 'pdf' ? 'pdf' : 'image',
                    'size' => number_format($file->getSize() / 1024 / 1024, 2) . ' MB',
                ];
            })
            ->values()
            ->all();
    }
}
