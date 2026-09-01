<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PrintIpcrfController extends Controller
{
    /**
     * Print IPCRF for Semestral Target (Ratee / Logged-in user)
     */
    public function show(Request $request): View
    {
        $semId = (int) $request->query('sem_id', 0);
        $userId = Auth::id();

        if ($semId <= 0 || ! $userId) {
            abort(404, __('Semestral target record not found.'));
        }

        // If explicitly requested as verification, delegate
        if ($request->query('type') === 'verification') {
            return $this->showVerification($request);
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
            abort(404, __('Semestral target record not found for logged in user.'));
        }

        return $this->renderIpcrfView($semRecord);
    }

    /**
     * Print IPCRF for Verification (Supervisor assessing staff user)
     */
    public function showVerification(Request $request): View
    {
        $semId = (int) $request->query('sem_id', 0);
        $authId = Auth::id();

        if ($semId <= 0 || ! $authId) {
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

        return $this->renderIpcrfView($semRecord);
    }

    protected function renderIpcrfView(object $semRecord): View
    {
        $semId = (int) $semRecord->semester_id;

        $rows = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'sti.id', '=', 'stil.sem_target_id')
            ->where('sti.semester_id', $semId)
            ->select([
                'sti.activity',
                'sti.kra_category',
                'stil.description',
                'stil.actual_accomp',
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

        $year = $semRecord->year ?? now()->year;
        $semester = $semRecord->semester ?? '';
        $rateePosition = mb_strtoupper((string) ($semRecord->ratee_designation ?: ($semRecord->ratee_position ?: '-')), 'UTF-8');
        $rateeDivision = $semRecord->ratee_division ?: 'PANTAWID PAMILYANG PILIPINO PROGRAM';
        $supPosition = mb_strtoupper((string) ($semRecord->sup_designation ?: ($semRecord->sup_position ?: 'DIVISION CHIEF')), 'UTF-8');
        $dateFormatted = Carbon::now('Asia/Manila')->format('F d, Y');

        return view('print.ipcrf', compact(
            'semRecord',
            'year',
            'semester',
            'rateeFullName',
            'rateePosition',
            'rateeDivision',
            'supFullName',
            'supPosition',
            'rows',
            'dateFormatted'
        ));
    }
}
