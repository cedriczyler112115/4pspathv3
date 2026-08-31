<?php

namespace App\Http\Controllers;

use App\Support\KraCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrintCheckpointController extends Controller
{
    public function show(Request $request): View
    {
        $semId = (int) $request->query('sem_id', 0);
        $userId = Auth::id();

        if ($semId <= 0 || !$userId) {
            abort(404, __('Semestral target record not found.'));
        }

        $semRecord = DB::table('ipc_semester as sem')
            ->leftJoin('users as u', 'sem.user_id', '=', 'u.id')
            ->leftJoin('lib_division as d', 'u.division_id', '=', 'd.id')
            ->leftJoin('lib_section as s', 'u.section_id', '=', 's.id')
            ->leftJoin('users as sup', 'u.supervisor_id', '=', 'sup.id')
            ->where('sem.id', $semId)
            ->select([
                'sem.id as semester_id',
                'sem.year',
                'sem.semester',
                'sem.user_id',
                'u.supervisor_id',
                'u.first_name as ratee_first_name',
                'u.middle_name as ratee_middle_name',
                'u.last_name as ratee_last_name',
                'u.position as ratee_position',
                'u.designation as ratee_designation',
                DB::raw('COALESCE(d.division_name, u.division) as ratee_division'),
                DB::raw('COALESCE(s.section_name, u.section) as ratee_section'),
                'sup.first_name as sup_first_name',
                'sup.middle_name as sup_middle_name',
                'sup.last_name as sup_last_name',
                'sup.position as sup_position',
                'sup.designation as sup_designation',
            ])
            ->first();

        if (!$semRecord) {
            abort(404, __('Semestral target record not found.'));
        }

        $rateeFullName = mb_strtoupper(trim(($semRecord->ratee_last_name ?? '') . (filled($semRecord->ratee_last_name) ? ', ' : '') . collect([$semRecord->ratee_first_name, $semRecord->ratee_middle_name])->filter()->join(' ')), 'UTF-8');
        $rateePosition = mb_strtoupper((string) ($semRecord->ratee_designation ?: ($semRecord->ratee_position ?: '-')), 'UTF-8');

        $supFullName = mb_strtoupper(trim(($semRecord->sup_last_name ?? '') . (filled($semRecord->sup_last_name) ? ', ' : '') . collect([$semRecord->sup_first_name, $semRecord->sup_middle_name])->filter()->join(' ')), 'UTF-8');
        $supPosition = mb_strtoupper((string) ($semRecord->sup_designation ?: ($semRecord->sup_position ?: 'DIVISION CHIEF')), 'UTF-8');

        $appFullName = 'ENTER APPROVED BY';
        $appPosition = 'ENTER POSITION / DESIGNATION';

        // Edit history records for this semester's targets (including deleted targets)
        $activeTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $semId)
            ->pluck('id')
            ->all();

        $allTargetIds = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('ipc_sem_targets_indicator as sti', 'h.sem_target_id', '=', 'sti.id')
            ->where(function ($q) use ($activeTargetIds, $semId) {
                if (! empty($activeTargetIds)) {
                    $q->whereIn('h.sem_target_id', $activeTargetIds);
                }
                $q->orWhere('sti.semester_id', $semId);
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

        $checkpointRows = collect();

        foreach ($groupedHistories as $semTargetId => $targetRecords) {
            $justifications = [];
            foreach ($targetRecords as $h) {
                if (filled($h->justification)) {
                    $justifications[] = $h->justification;
                }
            }

            // Check if the entire target was deleted
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

                $targetFields[] = (object) [
                    'field_name' => $fieldName,
                    'field_label' => $label,
                    'order_rank' => $orderRank,
                    'old_value' => $oldVal,
                    'new_value' => $newVal,
                ];
            }

            $hasActivity = collect($targetFields)->contains(fn ($f) => $f->field_name === 'activity');
            if (! $hasActivity && filled($activityTitle)) {
                array_unshift($targetFields, (object) [
                    'field_name' => 'activity',
                    'field_label' => 'Key Result Area',
                    'order_rank' => 1,
                    'old_value' => $activityTitle,
                    'new_value' => $isDeletedTarget ? 'For Deletion' : '-',
                ]);
            }

            usort($targetFields, fn ($a, $b) => $a->order_rank <=> $b->order_rank);

            if ($isDeletedTarget) {
                $targetFields = array_values(array_filter($targetFields, fn ($f) => in_array($f->field_name, ['kra_category', 'activity'], true)));
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
                        $iFields[] = (object) [
                            'field_name' => 'description',
                            'field_label' => 'Success Indicator (Measure + Target)',
                            'order_rank' => 2,
                            'old_value' => '-',
                            'new_value' => $desc,
                        ];
                    }

                    if (filled($iFirstRec->current_quantity)) {
                        $iFields[] = (object) [
                            'field_name' => 'rg_quantity',
                            'field_label' => 'Efficiency',
                            'order_rank' => 3,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_quantity,
                        ];
                    }

                    if (filled($iFirstRec->current_quality)) {
                        $iFields[] = (object) [
                            'field_name' => 'rg_quality',
                            'field_label' => 'Quality',
                            'order_rank' => 4,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_quality,
                        ];
                    }

                    if (filled($iFirstRec->current_timeliness)) {
                        $iFields[] = (object) [
                            'field_name' => 'rg_timeliness',
                            'field_label' => 'Timeliness',
                            'order_rank' => 5,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_timeliness,
                        ];
                    }

                    if (filled($iFirstRec->current_movs)) {
                        $iFields[] = (object) [
                            'field_name' => 'rg_movs',
                            'field_label' => 'MOVs',
                            'order_rank' => 6,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_movs,
                        ];
                    }

                    if (filled($iFirstRec->current_remarks)) {
                        $iFields[] = (object) [
                            'field_name' => 'rg_remarks',
                            'field_label' => 'Remarks',
                            'order_rank' => 7,
                            'old_value' => '-',
                            'new_value' => $iFirstRec->current_remarks,
                        ];
                    }
                } elseif ($isSubItemDeleted) {
                    // Deleted sub-target: show all original indicator values on the left, and 'For Deletion' for description on the right
                    $descRec = $iRecords->firstWhere('field_name', 'description');
                    $descOld = $descRec ? ($descRec->old_value ?: ($descRec->original_value ?: $iFirstRec->current_description)) : ($iFirstRec->current_description ?: '-');

                    $iFields[] = (object) [
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
                            $iFields[] = (object) [
                                'field_name' => $fn,
                                'field_label' => $meta[0],
                                'order_rank' => $meta[1],
                                'old_value' => $val,
                                'new_value' => '-',
                            ];
                        }
                    }
                } else {
                    // Normal updated sub-target: only include modified fields
                    foreach ($iRecords as $h) {
                        $fieldName = (string) $h->field_name;
                        if ($fieldName === 'deleted' || $fieldName === 'created') {
                            continue;
                        }

                        $label = $fieldLabels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
                        $orderRank = $fieldOrder[$fieldName] ?? 99;

                        $oldVal = $h->old_value ?: ($h->original_value ?: '-');
                        $newVal = $h->new_value ?: '-';

                        $iFields[] = (object) [
                            'field_name' => $fieldName,
                            'field_label' => $label,
                            'order_rank' => $orderRank,
                            'old_value' => $oldVal,
                            'new_value' => $newVal,
                        ];
                    }

                    usort($iFields, fn ($a, $b) => $a->order_rank <=> $b->order_rank);
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

                $itemGroups[] = (object) [
                    'item_id' => $itemId,
                    'item_label' => $itemLabel,
                    'is_created' => $isSubItemCreated,
                    'is_deleted' => $isSubItemDeleted,
                    'fields' => $iFields,
                ];

                $itemCounter++;
            }

            $justificationText = collect($justifications)->unique()->filter(fn ($j) => filled($j) && $j !== '-')->join('; ');

            $checkpointRows->push((object) [
                'sem_target_id' => $semTargetId,
                'activity_title' => $activityTitle,
                'is_new_target' => $isNewlyAdded && ! $isDeletedTarget,
                'is_deleted' => $isDeletedTarget,
                'target_fields' => $targetFields,
                'item_groups' => $itemGroups,
                'justification' => $justificationText ?: ($isDeletedTarget ? __('Target Deleted') : __('Target Entry / Update')),
            ]);
        }

        $dateFormatted = Carbon::now('Asia/Manila')->format('F d, Y');

        $activeRows = $checkpointRows->filter(fn ($r) => ! ($r->is_deleted ?? false))->values();
        $deletedRows = $checkpointRows->filter(fn ($r) => ($r->is_deleted ?? false))->values();
        $year = $semRecord->year ?? now()->year;
        $rateeDivision = $semRecord->ratee_division ?: 'PANTAWID PAMILYANG PILIPINO PROGRAM';

        return view('print.ipcrf-checkpoint', compact(
            'semRecord',
            'year',
            'rateeFullName',
            'rateePosition',
            'rateeDivision',
            'supFullName',
            'supPosition',
            'appFullName',
            'appPosition',
            'dateFormatted',
            'activeRows',
            'deletedRows',
            'checkpointRows'
        ));
    }
}
