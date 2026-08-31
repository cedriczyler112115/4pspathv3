<?php

namespace App\Http\Controllers\Inertia;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        $request->validate([
            'files' => ['required', 'array'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,ppt,pptx', 'max:20480'],
        ]);

        $destination = public_path('documentation');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($destination);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $originalName) ?: 'document';
                $ext = strtolower($file->getClientOriginalExtension());
                $fileName = $safeName . '_' . now()->format('YmdHis') . '_' . uniqid() . '.' . $ext;

                $file->move($destination, $fileName);
            }
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
        $histories = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('ipc_sem_targets_indicator as sti', 'h.sem_target_id', '=', 'sti.id')
            ->where('sti.semester_id', $ratingId)
            ->select([
                'h.id',
                'h.sem_target_id',
                'h.sem_item_id',
                'h.field_name',
                'h.old_value',
                'h.new_value',
                'h.justification',
                'h.date_created',
                'sti.activity',
            ])
            ->orderBy('h.id', 'asc')
            ->get();

        $grouped = [];
        foreach ($histories->groupBy('sem_target_id') as $targetId => $records) {
            $first = $records->first();
            $grouped[] = [
                'target_id' => $targetId,
                'activity_title' => $first->activity ?: 'Target Entry',
                'justification' => $records->pluck('justification')->filter()->first() ?: '-',
                'fields' => $records->map(fn ($r) => [
                    'field_label' => ucwords(str_replace('_', ' ', $r->field_name)),
                    'old_value' => $r->old_value ?: '-',
                    'new_value' => $r->new_value ?: '-',
                ])->values()->all(),
            ];
        }

        return $grouped;
    }

    private function getDocumentationFiles(): array
    {
        $dir = public_path('documentation');
        if (! \Illuminate\Support\Facades\File::exists($dir)) {
            return [];
        }

        $files = \Illuminate\Support\Facades\File::files($dir);
        $result = [];

        foreach ($files as $file) {
            $name = $file->getFilename();
            $mime = \Illuminate\Support\Facades\File::mimeType($file->getPathname()) ?: '';
            $ext = strtolower($file->getExtension());
            $isImage = str_contains($mime, 'image') || in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
            $isPdf = str_contains($mime, 'pdf') || $ext === 'pdf';

            $result[] = [
                'name' => $name,
                'path' => 'documentation/' . $name,
                'url' => asset('documentation/' . $name),
                'mime' => $mime,
                'size' => $file->getSize(),
                'type' => $isImage ? 'image' : ($isPdf ? 'pdf' : 'document'),
                'modified_at' => Carbon::createFromTimestamp($file->getMTime())->format('M d, Y h:i A'),
            ];
        }

        return $result;
    }

    public function updateAccomplishment(Request $request, int $ratingId, int $itemId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $item = DB::table('ipc_sem_targets_indicator_itemlist as stii')
            ->join('ipc_sem_targets_indicator as sti', 'stii.sem_target_id', '=', 'sti.id')
            ->join('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->where('stii.id', $itemId)
            ->where('sti.semester_id', $ratingId)
            ->where('sem.user_id', $userId)
            ->select(['stii.id', 'sti.id as sem_target_id'])
            ->first();

        abort_if($item === null, 404);

        $validated = $request->validate([
            'actualAccomplishment' => ['nullable', 'string'],
            'actQuality' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'actEfficiency' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'actTimeliness' => ['nullable', 'numeric', 'min:1', 'max:5'],
            'remarks' => ['nullable', 'string'],
        ]);

        $scores = array_filter([
            $validated['actQuality'] ?? null,
            $validated['actEfficiency'] ?? null,
            $validated['actTimeliness'] ?? null,
        ], fn ($v) => $v !== null && $v > 0);

        $average = !empty($scores) ? array_sum($scores) / count($scores) : null;

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->update([
                'actual_accomp' => $validated['actualAccomplishment'] ?? '',
                'quality_score' => $validated['actQuality'] ?? null,
                'quantity_score' => $validated['actEfficiency'] ?? null,
                'timeliness_score' => $validated['actTimeliness'] ?? null,
                'average' => $average ? number_format($average, 5, '.', '') : null,
                'rg_remarks' => $validated['remarks'] ?? '',
            ]);

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

        return back()->with('success', __('Edit history discarded and values reverted to original successfully.'));
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

        $action = $request->input('action'); // 'ready', 'unlock'

        if ($action === 'ready') {
            DB::table('ipc_semester')
                ->where('id', $ratingId)
                ->update([
                    'lock' => 2,
                    'is_ready' => 1,
                    'date_ready' => Carbon::now('Asia/Manila'),
                ]);
            return back()->with('success', __('Semestral targets locked and submitted for verification.'));
        } elseif ($action === 'unlock') {
            DB::table('ipc_semester')
                ->where('id', $ratingId)
                ->update([
                    'lock' => 0,
                    'is_ready' => 0,
                ]);
            return back()->with('success', __('Semestral targets unlocked for draft edits.'));
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

    public function copyStaffMovs(Request $request, int $ratingId): RedirectResponse
    {
        $userId = Auth::id();
        abort_if($userId === null, 403);

        $validated = $request->validate([
            'sourceItemId' => ['required', 'integer'],
            'targetItemId' => ['required', 'integer'],
        ]);

        $source = DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $validated['sourceItemId'])
            ->first();

        if ($source && !empty($source->rg_movs)) {
            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $validated['targetItemId'])
                ->update([
                    'rg_movs' => $source->rg_movs,
                ]);
        }

        return back()->with('success', __('MOVs copied from staff target.'));
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
}
