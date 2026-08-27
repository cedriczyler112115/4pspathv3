<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrintIpcrfController extends Controller
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
            ->where('sem.user_id', $userId)
            ->select([
                'sem.id as semester_id',
                'sem.year',
                'sem.semester',
                'u.first_name as ratee_first_name',
                'u.middle_name as ratee_middle_name',
                'u.last_name as ratee_last_name',
                'u.position as ratee_position',
                'u.designation as ratee_designation',
                DB::raw('COALESCE(d.division_name, u.division) as ratee_division'),
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

        $rows = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'sti.id', '=', 'stil.sem_target_id')
            ->where('sti.semester_id', $semId)
            ->select([
                'sti.activity',
                'sti.kra_category',
                'stil.description',
                'stil.rg_quantity',
                'stil.rg_quality',
                'stil.rg_timeliness',
                'stil.average',
                'stil.target_movs',
                'stil.target_remarks',
            ])
            ->orderBy('sti.kra_category')
            ->orderBy('sti.display_order')
            ->orderBy('stil.display_order')
            ->get();

        $rateeFullName = mb_strtoupper(trim(($semRecord->ratee_last_name ?? '') . (filled($semRecord->ratee_last_name) ? ', ' : '') . collect([$semRecord->ratee_first_name, $semRecord->ratee_middle_name])->filter()->join(' ')), 'UTF-8');
        $supFullName = mb_strtoupper(trim(($semRecord->sup_last_name ?? '') . (filled($semRecord->sup_last_name) ? ', ' : '') . collect([$semRecord->sup_first_name, $semRecord->sup_middle_name])->filter()->join(' ')), 'UTF-8');

        return view('print.ipcrf', [
            'semRecord' => $semRecord,
            'year' => $semRecord->year ?? now()->year,
            'semester' => $semRecord->semester ?? '',
            'rateeFullName' => $rateeFullName,
            'rateePosition' => mb_strtoupper((string) ($semRecord->ratee_designation ?: ($semRecord->ratee_position ?: '-')), 'UTF-8'),
            'rateeDivision' => $semRecord->ratee_division ?: 'PANTAWID PAMILYANG PILIPINO PROGRAM',
            'supFullName' => $supFullName,
            'supPosition' => mb_strtoupper((string) ($semRecord->sup_designation ?: ($semRecord->sup_position ?: 'DIVISION CHIEF')), 'UTF-8'),
            'rows' => $rows,
            'dateFormatted' => Carbon::now('Asia/Manila')->format('F d, Y'),
        ]);
    }
}
