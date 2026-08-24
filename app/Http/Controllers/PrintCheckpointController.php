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

        if ($semId <= 0 || ! $userId) {
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

        if (! $semRecord) {
            abort(404, __('Semestral target record not found.'));
        }

        $rateeFullName = mb_strtoupper(trim(($semRecord->ratee_last_name ?? '').(filled($semRecord->ratee_last_name) ? ', ' : '').collect([$semRecord->ratee_first_name, $semRecord->ratee_middle_name])->filter()->join(' ')), 'UTF-8');
        $rateePosition = mb_strtoupper((string) ($semRecord->ratee_designation ?: ($semRecord->ratee_position ?: '-')), 'UTF-8');

        $supFullName = mb_strtoupper(trim(($semRecord->sup_last_name ?? '').(filled($semRecord->sup_last_name) ? ', ' : '').collect([$semRecord->sup_first_name, $semRecord->sup_middle_name])->filter()->join(' ')), 'UTF-8');
        $supPosition = mb_strtoupper((string) ($semRecord->sup_designation ?: ($semRecord->sup_position ?: 'DIVISION CHIEF')), 'UTF-8');

        $appFullName = 'ENTER APPROVED BY';
        $appPosition = 'ENTER POSITION / DESIGNATION';

        // Current active targets and itemlist entries
        $activeTargets = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'sti.id', '=', 'stil.sem_target_id')
            ->where('sti.semester_id', $semId)
            ->select([
                'sti.id as sem_target_id',
                'sti.kra_category',
                'sti.activity',
                'stil.id as sem_item_id',
                'stil.description',
                'stil.rg_quantity',
                'stil.rg_quality',
                'stil.rg_timeliness',
                'stil.rg_movs',
                'stil.rg_remarks',
            ])
            ->orderBy('sti.kra_category')
            ->orderBy('sti.display_order')
            ->orderBy('stil.display_order')
            ->get();

        // Edit history records for this semester
        $semTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $semId)
            ->pluck('id');

        $histories = DB::table('ipc_sem_target_edit_histories')
            ->whereIn('sem_target_id', $semTargetIds)
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy(fn ($h) => $h->sem_target_id.'-'.($h->sem_item_id ?? 0));

        $checkpointRows = collect();

        // 1. Process active targets and itemlist entries
        foreach ($activeTargets as $item) {
            $key = $item->sem_target_id.'-'.$item->sem_item_id;
            $itemHistories = $histories->get($key, collect());

            $origCategory = (int) $item->kra_category;
            $origActivity = (string) $item->activity;
            $origDescription = (string) $item->description;

            $hasChanges = false;
            $proposedCategory = null;
            $proposedActivity = null;
            $proposedDescription = null;
            $justifications = [];

            foreach ($itemHistories as $h) {
                if (filled($h->justification)) {
                    $justifications[] = $h->justification;
                }

                if ($h->field_name === 'kra_category' && filled($h->original_value)) {
                    $origCategory = (int) $h->original_value;
                    $proposedCategory = (int) ($h->new_value ?? $item->kra_category);
                    $hasChanges = true;
                }
                if ($h->field_name === 'activity' && filled($h->original_value)) {
                    $origActivity = (string) $h->original_value;
                    $proposedActivity = (string) ($h->new_value ?? $item->activity);
                    $hasChanges = true;
                }
                if ($h->field_name === 'description' && filled($h->original_value)) {
                    $origDescription = (string) $h->original_value;
                    $proposedDescription = (string) ($h->new_value ?? $item->description);
                    $hasChanges = true;
                }
            }

            $justificationText = collect($justifications)->unique()->filter()->join('; ');

            $checkpointRows->push((object) [
                'type' => 'active',
                'is_deleted' => false,
                'orig_category' => KraCategory::label($origCategory),
                'orig_activity' => $origActivity,
                'orig_description' => $origDescription,
                'proposed_category' => $proposedCategory !== null ? KraCategory::label($proposedCategory) : KraCategory::label((int) $item->kra_category),
                'proposed_activity' => $proposedActivity ?? (string) $item->activity,
                'proposed_description' => $proposedDescription ?? (string) $item->description,
                'has_changes' => $hasChanges,
                'justification' => $justificationText ?: __('Target Entry / Update'),
            ]);
        }

        // 2. Process deleted targets logged in history
        $deletedHistories = DB::table('ipc_sem_target_edit_histories')
            ->whereIn('sem_target_id', $semTargetIds)
            ->where('field_name', 'deleted')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($deletedHistories as $del) {
            $checkpointRows->push((object) [
                'type' => 'deleted',
                'is_deleted' => true,
                'orig_category' => KraCategory::label(2),
                'orig_activity' => (string) ($del->original_value ?? $del->old_value ?? 'Deleted Target'),
                'orig_description' => (string) ($del->old_value ?? $del->original_value ?? ''),
                'proposed_category' => null,
                'proposed_activity' => null,
                'proposed_description' => 'For Deletion',
                'has_changes' => true,
                'justification' => (string) ($del->justification ?: 'wrong entry'),
            ]);
        }

        $formattedDate = Carbon::now('Asia/Manila')->format('F d, Y');

        return view('print.ipcrf-checkpoint', [
            'semRecord' => $semRecord,
            'year' => $semRecord->year ?? now()->year,
            'rateeFullName' => $rateeFullName,
            'rateePosition' => $rateePosition,
            'rateeDivision' => $semRecord->ratee_division ?: 'PANTAWID PAMILYANG PILIPINO PROGRAM',
            'supFullName' => $supFullName,
            'supPosition' => $supPosition,
            'appFullName' => $appFullName,
            'appPosition' => $appPosition,
            'dateFormatted' => $formattedDate,
            'checkpointRows' => $checkpointRows,
        ]);
    }
}
