<?php

namespace App\Livewire\SemestralTarget;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class IndicatorRows extends Component
{
    #[Locked]
    public int $indicatorId;

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public bool $editing = false;

    public bool $creatingSubTarget = false;

    public string $editActivity = '';

    public string $editCategory = '';

    /** @var array<int, array{description:string, quantity:string, quality:string, timeliness:string, movs:string, remarks:string}> */
    public array $editRows = [];

    /** @var array<int, array{description:string, quantity:string, quality:string, timeliness:string, movs:string, remarks:string}> */
    public array $pendingSubTargets = [];

    public bool $showJustificationModal = false;

    public string $justificationText = '';

    /** @var array<int, array{quantity_score:string, quality_score:string, timeliness_score:string, average:string}> */
    public array $scores = [];

    public bool $isSemesterLocked = false;

    /** @param array<int, array<string, mixed>> $rows */
    public function mount(int $indicatorId, array $rows, bool $isSemesterLocked = false): void
    {
        $this->indicatorId = $indicatorId;
        $this->rows = $rows;

        $semId = request()->query('sem_id');
        if (!$semId && !empty($rows[0]['semester_id'])) {
            $semId = (int) $rows[0]['semester_id'];
        }

        if ($semId) {
            $lock = DB::table('ipc_semester')->where('id', $semId)->value('lock');
            $this->isSemesterLocked = ((int) $lock === 1);
        } else {
            $this->isSemesterLocked = $isSemesterLocked;
        }

        $this->initScores();
    }

    /** @var array<int, object|null> */
    protected static array $semesterRecordCache = [];

    protected function is2026SecondSemesterOrBeyond(): bool
    {
        $semesterId = 0;
        $firstRow = $this->rows[0] ?? null;

        if ($firstRow && ! empty($firstRow['semester_id'])) {
            $semesterId = (int) $firstRow['semester_id'];
        } else {
            $semesterId = (int) DB::table('ipc_sem_targets_indicator')
                ->where('id', $this->indicatorId)
                ->value('semester_id');
        }

        if ($semesterId <= 0) {
            return false;
        }

        if (! array_key_exists($semesterId, static::$semesterRecordCache)) {
            static::$semesterRecordCache[$semesterId] = DB::table('ipc_semester')->where('id', $semesterId)->first();
        }

        $semRecord = static::$semesterRecordCache[$semesterId];
        if (! $semRecord) {
            return false;
        }

        $year = (int) ($semRecord->year ?? 0);
        $sem = (int) ($semRecord->semester ?? 0);

        if ($year > 2026) {
            return true;
        }

        if ($year === 2026 && $sem >= 2) {
            return true;
        }

        return false;
    }

    public function edit(): void
    {
        $firstRow = $this->rows[0] ?? null;

        if ($firstRow === null) {
            return;
        }

        $itemIds = collect($this->rows)
            ->pluck('sem_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $createdHistories = DB::table('ipc_sem_target_edit_histories')
            ->where('field_name', 'created')
            ->where(function ($q) use ($itemIds) {
                $q->where('sem_target_id', $this->indicatorId);
                if (! empty($itemIds)) {
                    $q->orWhereIn('sem_item_id', $itemIds);
                }
            })
            ->get();

        $createdItemIds = $createdHistories
            ->pluck('sem_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->all();

        $isTargetNewlyAdded = $createdHistories->contains(fn ($r) => (int) $r->sem_target_id === $this->indicatorId && empty($r->sem_item_id));

        $editableRows = [];
        foreach ($this->rows as $row) {
            $itemId = (int) ($row['sem_item_id'] ?? 0);
            $isNewlyAdded = in_array($itemId, $createdItemIds, true);

            if (! $isNewlyAdded) {
                $editableRows[$itemId] = [
                    'description' => $this->normalizeTextareaValue($row['description'] ?? ''),
                    'quantity' => $this->normalizeTextareaValue($row['rg_quantity'] ?? ''),
                    'quality' => $this->normalizeTextareaValue($row['rg_quality'] ?? ''),
                    'timeliness' => $this->normalizeTextareaValue($row['rg_timeliness'] ?? ''),
                    'movs' => $this->normalizeTextareaValue($row['rg_movs'] ?? ''),
                    'remarks' => $this->normalizeTextareaValue($row['rg_remarks'] ?? ''),
                ];
            }
        }

        if (empty($editableRows)) {
            Flux::toast(variant: 'warning', text: __('Newly added targets cannot be edited. You can delete and re-add them if changes are needed.'));

            return;
        }

        $this->editing = true;
        $this->creatingSubTarget = false;
        $this->editActivity = $this->normalizeTextareaValue($firstRow['activity'] ?? '');
        $this->editCategory = (string) ($firstRow['kra_category'] ?? '');
        $this->editRows = $editableRows;
    }

    public function cancel(): void
    {
        $this->editing = false;
        $this->creatingSubTarget = false;
        $this->editActivity = '';
        $this->editCategory = '';
        $this->editRows = [];
        $this->pendingSubTargets = [];
        $this->showJustificationModal = false;
        $this->justificationText = '';
    }

    public function save(): void
    {
        $userId = Auth::id();

        if (! $this->editing || $userId === null) {
            return;
        }

        $is2026Sem2 = $this->is2026SecondSemesterOrBeyond();

        if ($is2026Sem2 && ! $this->creatingSubTarget && ! $this->showJustificationModal) {
            $existingJustification = DB::table('ipc_sem_target_edit_histories')
                ->where('sem_target_id', $this->indicatorId)
                ->whereNotNull('justification')
                ->where('justification', '!=', '')
                ->latest('id')
                ->value('justification');

            $this->justificationText = (string) ($existingJustification ?? '');
            $this->showJustificationModal = true;

            return;
        }

        if ($is2026Sem2 && ! $this->creatingSubTarget && empty(trim($this->justificationText))) {
            Flux::toast(variant: 'danger', text: __('Justification is required before saving changes.'));

            return;
        }

        $nowManila = Carbon::now('Asia/Manila');

        DB::transaction(function () use ($userId, $nowManila, $is2026Sem2): void {
            if (! $this->creatingSubTarget) {
                $firstRow = $this->rows[0] ?? null;
                $oldActivity = $firstRow ? $this->normalizeTextareaValue($firstRow['activity'] ?? '') : '';
                $oldCategory = $firstRow ? (string) ($firstRow['kra_category'] ?? '') : '';

                if ($is2026Sem2) {
                    $this->logFieldHistory($this->indicatorId, null, 'activity', $oldActivity, $this->editActivity, $userId, $nowManila);
                    $this->logFieldHistory($this->indicatorId, null, 'kra_category', $oldCategory, (string) $this->editCategory, $userId, $nowManila);
                }

                DB::table('ipc_sem_targets_indicator')
                    ->where('id', $this->indicatorId)
                    ->update([
                        'activity' => $this->editActivity,
                        'kra_category' => (int) $this->editCategory,
                        'modified_by' => $userId,
                        'last_date_modified' => $nowManila,
                    ]);

                foreach ($this->editRows as $itemId => $values) {
                    $itemRow = collect($this->rows)->firstWhere('sem_item_id', $itemId);
                    $oldDesc = $itemRow ? $this->normalizeTextareaValue($itemRow['description'] ?? '') : '';
                    $oldQty = $itemRow ? $this->normalizeTextareaValue($itemRow['rg_quantity'] ?? '') : '';
                    $oldQual = $itemRow ? $this->normalizeTextareaValue($itemRow['rg_quality'] ?? '') : '';
                    $oldTime = $itemRow ? $this->normalizeTextareaValue($itemRow['rg_timeliness'] ?? '') : '';
                    $oldMovs = $itemRow ? $this->normalizeTextareaValue($itemRow['rg_movs'] ?? '') : '';
                    $oldRem = $itemRow ? $this->normalizeTextareaValue($itemRow['rg_remarks'] ?? '') : '';

                    $newDesc = $values['description'] ?? '';
                    $newQty = $values['quantity'] ?? '';
                    $newQual = $values['quality'] ?? '';
                    $newTime = $values['timeliness'] ?? '';
                    $newMovs = $values['movs'] ?? '';
                    $newRem = $values['remarks'] ?? '';

                    if ($is2026Sem2) {
                        $this->logFieldHistory($this->indicatorId, (int) $itemId, 'description', $oldDesc, $newDesc, $userId, $nowManila);
                        $this->logFieldHistory($this->indicatorId, (int) $itemId, 'rg_quantity', $oldQty, $newQty, $userId, $nowManila);
                        $this->logFieldHistory($this->indicatorId, (int) $itemId, 'rg_quality', $oldQual, $newQual, $userId, $nowManila);
                        $this->logFieldHistory($this->indicatorId, (int) $itemId, 'rg_timeliness', $oldTime, $newTime, $userId, $nowManila);
                        $this->logFieldHistory($this->indicatorId, (int) $itemId, 'rg_movs', $oldMovs, $newMovs, $userId, $nowManila);
                        $this->logFieldHistory($this->indicatorId, (int) $itemId, 'rg_remarks', $oldRem, $newRem, $userId, $nowManila);
                    }

                    DB::table('ipc_sem_targets_indicator_itemlist')
                        ->where('id', (int) $itemId)
                        ->where('sem_target_id', $this->indicatorId)
                        ->update([
                            'description' => $newDesc,
                            'rg_quantity' => $newQty ?: null,
                            'rg_quality' => $newQual ?: null,
                            'rg_timeliness' => $newTime ?: null,
                            'rg_movs' => $newMovs ?: null,
                            'rg_remarks' => $newRem ?: null,
                            'modified_by' => $userId,
                            'date_modified' => $nowManila,
                        ]);
                }
            }

            if ($this->creatingSubTarget) {
                $firstRow = $this->rows[0] ?? null;
                $semesterId = (int) ($firstRow['semester_id'] ?? 0);

                foreach ($this->pendingSubTargets as $pendingSubTarget) {
                    $nextItemOrder = ((int) DB::table('ipc_sem_targets_indicator_itemlist')
                        ->where('sem_target_id', $this->indicatorId)
                        ->max('display_order')) + 1;

                    $insertedId = (int) DB::table('ipc_sem_targets_indicator_itemlist')->insertGetId([
                        'target_orig_id' => 0,
                        'sem_target_id' => $this->indicatorId,
                        'display_order' => $nextItemOrder,
                        'sem_item_id' => $semesterId,
                        'new_semester' => null,
                        'description' => $pendingSubTarget['description'] ?? '',
                        'actual_accomp' => null,
                        'weight' => null,
                        'quantity' => null,
                        'quality' => null,
                        'timeliness' => null,
                        'rg_quantity' => $pendingSubTarget['quantity'] ?? null,
                        'rg_quality' => $pendingSubTarget['quality'] ?? null,
                        'rg_timeliness' => $pendingSubTarget['timeliness'] ?? null,
                        'rg_movs' => $pendingSubTarget['movs'] ?? null,
                        'rg_remarks' => $pendingSubTarget['remarks'] ?? null,
                        'remarks' => 1,
                        'created_by' => $userId,
                        'date_created' => $nowManila,
                        'modified_by' => $userId,
                        'date_modified' => $nowManila,
                    ]);

                    if ($insertedId > 0) {
                        $this->logFieldHistory($this->indicatorId, $insertedId, 'description', '', (string) ($pendingSubTarget['description'] ?? ''), $userId, $nowManila);
                        $this->logFieldHistory($this->indicatorId, $insertedId, 'created', '', 'Sub-target Added', $userId, $nowManila);

                        $this->rows[] = [
                            'sem_target_id' => $this->indicatorId,
                            'semester_id' => $semesterId,
                            'kra_category' => $firstRow['kra_category'] ?? 1,
                            'activity' => $firstRow['activity'] ?? '',
                            'sem_item_id' => $insertedId,
                            'description' => $pendingSubTarget['description'] ?? '',
                            'rg_quantity' => $pendingSubTarget['quantity'] ?? '',
                            'rg_quality' => $pendingSubTarget['quality'] ?? '',
                            'rg_timeliness' => $pendingSubTarget['timeliness'] ?? '',
                            'rg_movs' => $pendingSubTarget['movs'] ?? '',
                            'rg_remarks' => $pendingSubTarget['remarks'] ?? '',
                        ];
                    }
                }
            }
        });

        if (! $this->creatingSubTarget) {
            foreach ($this->rows as &$row) {
                $itemId = (int) ($row['sem_item_id'] ?? 0);
                $values = $this->editRows[$itemId] ?? null;

                if ($values === null) {
                    continue;
                }

                $row['activity'] = $this->editActivity;
                $row['kra_category'] = (int) $this->editCategory;
                $row['description'] = $values['description'];
                $row['rg_quantity'] = $values['quantity'];
                $row['rg_quality'] = $values['quality'];
                $row['rg_timeliness'] = $values['timeliness'];
                $row['rg_movs'] = $values['movs'];
                $row['rg_remarks'] = $values['remarks'];
            }
            unset($row);
        }

        $this->cancel();
        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Semestral target updated successfully.'));
    }

    protected function logFieldHistory(int $semTargetId, ?int $semItemId, string $fieldName, string $oldVal, string $newVal, int $userId, Carbon $now): void
    {
        if ($oldVal === $newVal) {
            return;
        }

        $query = DB::table('ipc_sem_target_edit_histories')
            ->where('sem_target_id', $semTargetId)
            ->where('field_name', $fieldName);

        if ($semItemId !== null) {
            $query->where('sem_item_id', $semItemId);
        } else {
            $query->whereNull('sem_item_id');
        }

        $existingHistory = (clone $query)->first();

        if ($existingHistory !== null) {
            $query->update([
                'old_value' => $oldVal,
                'new_value' => $newVal,
                'last_edited_value' => $oldVal,
                'justification' => trim($this->justificationText),
                'user_id' => $userId,
                'date_created' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('ipc_sem_target_edit_histories')->insert([
                'sem_target_id' => $semTargetId,
                'sem_item_id' => $semItemId,
                'field_name' => $fieldName,
                'original_value' => $oldVal,
                'old_value' => $oldVal,
                'new_value' => $newVal,
                'last_edited_value' => $oldVal,
                'justification' => trim($this->justificationText),
                'user_id' => $userId,
                'date_created' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function requestAddSubTarget(): void
    {
        $firstRow = $this->rows[0] ?? null;
        if ($firstRow === null) {
            return;
        }

        $this->editing = true;
        $this->creatingSubTarget = true;
        $this->editRows = [];
        $this->pendingSubTargets[] = [
            'description' => '',
            'quantity' => '',
            'quality' => '',
            'timeliness' => '',
            'movs' => '',
            'remarks' => '',
        ];
    }

    public function requestDelete(): void
    {
        $this->dispatch('semestral-target-delete-requested', semTargetId: $this->indicatorId);
    }

    public function requestDeleteSubTarget(int $itemId): void
    {
        if ($itemId > 0) {
            $this->dispatch('semestral-target-subtarget-delete-requested', semItemId: $itemId);
        }
    }

    #[On('semestral-target-updated')]
    public function refreshComponent(): void
    {
        $dbRows = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stii', 'stii.sem_target_id', '=', 'sti.id')
            ->where('sti.id', $this->indicatorId)
            ->select([
                'sti.id as sem_target_id',
                'sti.semester_id',
                'sti.kra_category',
                'sti.activity',
                'sti.target_status',
                'stii.id as sem_item_id',
                'stii.description',
                'stii.rg_quantity',
                'stii.rg_quality',
                'stii.rg_timeliness',
                'stii.rg_movs',
                'stii.rg_remarks',
                'stii.remarks',
                'stii.quantity_score',
                'stii.quality_score',
                'stii.timeliness_score',
                'stii.na_quantity',
                'stii.na_quality',
                'stii.na_timeliness',
                'stii.average',
                'stii.actual_accomp',
                'stii.target_movs',
                'stii.target_remarks',
            ])
            ->get();

        if ($dbRows->isNotEmpty()) {
            $this->rows = $dbRows->map(fn ($r) => (array) $r)->all();
            $this->scores = [];
            $this->initScores();
        }
    }

    public function initScores(): void
    {
        foreach ($this->rows as $row) {
            $itemId = (int) ($row['sem_item_id'] ?? 0);
            if ($itemId > 0 && ! isset($this->scores[$itemId])) {
                $q = $row['quantity_score'] ?? null;
                $ql = $row['quality_score'] ?? null;
                $t = $row['timeliness_score'] ?? null;
                $naQ = (int) ($row['na_quantity'] ?? 0);
                $naQl = (int) ($row['na_quality'] ?? 0);
                $naT = (int) ($row['na_timeliness'] ?? 0);
                $ave = $row['average'] ?? null;
                $acc = $row['actual_accomp'] ?? null;
                $movs = filled($row['target_movs'] ?? null) ? $row['target_movs'] : ($row['rg_movs'] ?? null);
                $rem = filled($row['target_remarks'] ?? null) ? $row['target_remarks'] : ($row['rg_remarks'] ?? null);

                // Fallback to 'N/A' text if na_quantity = 1, na_quality = 1, or na_timeliness = 1
                $qStr = $naQ === 1 ? 'N/A' : ($q !== null && $q !== '' ? (string) $q : '');
                $qlStr = $naQl === 1 ? 'N/A' : ($ql !== null && $ql !== '' ? (string) $ql : '');
                $tStr = $naT === 1 ? 'N/A' : ($t !== null && $t !== '' ? (string) $t : '');

                // Fallback inference if na_* column was not populated previously
                if ($qStr === '' && $q === null && ($ave !== null || $ql !== null || $t !== null)) {
                    $qStr = 'N/A';
                }
                if ($qlStr === '' && $ql === null && ($ave !== null || $q !== null || $t !== null)) {
                    $qlStr = 'N/A';
                }
                if ($tStr === '' && $t === null && ($ave !== null || $q !== null || $ql !== null)) {
                    $tStr = 'N/A';
                }

                $this->scores[$itemId] = [
                    'quantity_score' => $qStr,
                    'quality_score' => $qlStr,
                    'timeliness_score' => $tStr,
                    'average' => $ave !== null && $ave !== '' ? number_format((float) $ave, 2, '.', '') : ($qStr === 'N/A' || $qlStr === 'N/A' || $tStr === 'N/A' ? 'N/A' : ''),
                    'actual_accomp' => $acc !== null ? (string) $acc : '',
                    'target_movs' => $movs !== null ? (string) $movs : '',
                    'target_remarks' => $rem !== null ? (string) $rem : '',
                ];
            }
        }
    }

    public function updatedScores(mixed $value, string $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) < 2) {
            return;
        }

        $itemId = (int) $parts[0];
        $field = $parts[1];

        if ($itemId <= 0 || ! in_array($field, ['quantity_score', 'quality_score', 'timeliness_score', 'actual_accomp', 'target_movs', 'target_remarks'], true)) {
            return;
        }

        $itemScores = $this->scores[$itemId] ?? [];

        $qRaw = strtoupper(trim((string) ($itemScores['quantity_score'] ?? '')));
        $qlRaw = strtoupper(trim((string) ($itemScores['quality_score'] ?? '')));
        $tRaw = strtoupper(trim((string) ($itemScores['timeliness_score'] ?? '')));

        $isQNa = in_array($qRaw, ['N/A', 'NA', 'N/A.'], true);
        $isQlNa = in_array($qlRaw, ['N/A', 'NA', 'N/A.'], true);
        $isTNa = in_array($tRaw, ['N/A', 'NA', 'N/A.'], true);

        $parseScore = function (string $raw, bool $isNa): mixed {
            if ($isNa) {
                return 'N/A';
            }
            if ($raw === '' || ! is_numeric($raw)) {
                return null;
            }
            $val = (float) $raw;
            if ($val > 5) {
                $val = 5.0;
            }
            if ($val < 0) {
                return 0.0;
            }
            return round($val, 2);
        };

        $q = $parseScore($qRaw, $isQNa);
        $ql = $parseScore($qlRaw, $isQlNa);
        $t = $parseScore($tRaw, $isTNa);

        if ($q === 'N/A') {
            $this->scores[$itemId]['quantity_score'] = 'N/A';
        } elseif ($q !== null && is_numeric($qRaw)) {
            if ((float) $qRaw > 5) {
                $this->scores[$itemId]['quantity_score'] = '5';
            }
        }

        if ($ql === 'N/A') {
            $this->scores[$itemId]['quality_score'] = 'N/A';
        } elseif ($ql !== null && is_numeric($qlRaw)) {
            if ((float) $qlRaw > 5) {
                $this->scores[$itemId]['quality_score'] = '5';
            }
        }

        if ($t === 'N/A') {
            $this->scores[$itemId]['timeliness_score'] = 'N/A';
        } elseif ($t !== null && is_numeric($tRaw)) {
            if ((float) $tRaw > 5) {
                $this->scores[$itemId]['timeliness_score'] = '5';
            }
        }

        // Calculate average using ONLY numeric scores
        $validNumericScores = [];
        if (is_numeric($q)) {
            $validNumericScores[] = (float) $q;
        }
        if (is_numeric($ql)) {
            $validNumericScores[] = (float) $ql;
        }
        if (is_numeric($t)) {
            $validNumericScores[] = (float) $t;
        }

        if (! empty($validNumericScores)) {
            $average = round(array_sum($validNumericScores) / count($validNumericScores), 2);
            $avgStr = number_format($average, 2, '.', '');
        } else {
            $average = null;
            $avgStr = ($q === 'N/A' || $ql === 'N/A' || $t === 'N/A') ? 'N/A' : '';
        }

        $this->scores[$itemId]['average'] = $avgStr;

        $actualAccomp = isset($itemScores['actual_accomp']) ? (string) $itemScores['actual_accomp'] : null;
        $targetMovs = isset($itemScores['target_movs']) ? (string) $itemScores['target_movs'] : null;
        $targetRemarks = isset($itemScores['target_remarks']) ? (string) $itemScores['target_remarks'] : null;

        $nowManila = Carbon::now('Asia/Manila');
        $userId = Auth::id();

        $dbQ = is_numeric($q) ? (float) $q : null;
        $dbQl = is_numeric($ql) ? (float) $ql : null;
        $dbT = is_numeric($t) ? (float) $t : null;

        $naQ = ($q === 'N/A') ? 1 : null;
        $naQl = ($ql === 'N/A') ? 1 : null;
        $naT = ($t === 'N/A') ? 1 : null;

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->update([
                'quantity_score' => $dbQ,
                'quality_score' => $dbQl,
                'timeliness_score' => $dbT,
                'na_quantity' => $naQ,
                'na_quality' => $naQl,
                'na_timeliness' => $naT,
                'average' => $average,
                'actual_accomp' => $actualAccomp,
                'target_movs' => $targetMovs,
                'target_remarks' => $targetRemarks,
                'date_modified' => $nowManila,
                'modified_by' => $userId,
            ]);

        $this->dispatch('semestral-target-updated');
    }

    public function batchSaveScores(array $items): void
    {
        if (empty($items)) {
            return;
        }

        $nowManila = Carbon::now('Asia/Manila');
        $userId = Auth::id() ?: 1;

        foreach ($items as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }

            $qRaw = strtoupper(trim((string) ($item['quantity_score'] ?? '')));
            $qlRaw = strtoupper(trim((string) ($item['quality_score'] ?? '')));
            $tRaw = strtoupper(trim((string) ($item['timeliness_score'] ?? '')));
            $avgRaw = trim((string) ($item['average'] ?? ''));

            $isQNa = in_array($qRaw, ['N/A', 'NA', 'N/A.'], true);
            $isQlNa = in_array($qlRaw, ['N/A', 'NA', 'N/A.'], true);
            $isTNa = in_array($tRaw, ['N/A', 'NA', 'N/A.'], true);

            $dbQ = is_numeric($qRaw) ? (float) $qRaw : null;
            $dbQl = is_numeric($qlRaw) ? (float) $qlRaw : null;
            $dbT = is_numeric($tRaw) ? (float) $tRaw : null;

            if ($dbQ !== null && $dbQ > 5) $dbQ = 5.0;
            if ($dbQl !== null && $dbQl > 5) $dbQl = 5.0;
            if ($dbT !== null && $dbT > 5) $dbT = 5.0;

            $naQ = $isQNa ? 1 : null;
            $naQl = $isQlNa ? 1 : null;
            $naT = $isTNa ? 1 : null;

            $dbAverage = is_numeric($avgRaw) ? round((float) $avgRaw, 2) : null;

            $actualAccomp = isset($item['actual_accomp']) ? (string) $item['actual_accomp'] : null;
            $targetMovs = isset($item['target_movs']) ? (string) $item['target_movs'] : null;
            $targetRemarks = isset($item['target_remarks']) ? (string) $item['target_remarks'] : null;

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $itemId)
                ->update([
                    'quantity_score' => $dbQ,
                    'quality_score' => $dbQl,
                    'timeliness_score' => $dbT,
                    'na_quantity' => $naQ,
                    'na_quality' => $naQl,
                    'na_timeliness' => $naT,
                    'average' => $dbAverage,
                    'actual_accomp' => $actualAccomp,
                    'target_movs' => $targetMovs,
                    'target_remarks' => $targetRemarks,
                    'date_modified' => $nowManila,
                    'modified_by' => $userId,
                ]);

            $this->scores[$itemId] = [
                'quantity_score' => $isQNa ? 'N/A' : ($dbQ !== null ? (string) $dbQ : ''),
                'quality_score' => $isQlNa ? 'N/A' : ($dbQl !== null ? (string) $dbQl : ''),
                'timeliness_score' => $isTNa ? 'N/A' : ($dbT !== null ? (string) $dbT : ''),
                'average' => $dbAverage !== null ? number_format($dbAverage, 2, '.', '') : (($isQNa || $isQlNa || $isTNa) && $dbAverage === null ? 'N/A' : ''),
                'actual_accomp' => $actualAccomp ?? '',
                'target_movs' => $targetMovs ?? '',
                'target_remarks' => $targetRemarks ?? '',
            ];
        }

        $this->dispatch('semestral-target-updated');
    }

    public function render(): View
    {
        $this->initScores();
        $includeStrategic = \App\Models\ApplicationSetting::boolean('include_strategic_function', true);

        $categories = collect([
            (object) ['value' => '1', 'label' => 'Strategic Function'],
            (object) ['value' => '2', 'label' => 'Core Function'],
            (object) ['value' => '3', 'label' => 'Support Function'],
        ])->when(! $includeStrategic, fn ($cats) => $cats->reject(fn ($c) => $c->value === '1')->values());

        $itemIds = collect($this->rows)
            ->pluck('sem_item_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $historyRecords = DB::table('ipc_sem_target_edit_histories')
            ->where(function ($query) use ($itemIds) {
                $query->where('sem_target_id', $this->indicatorId);
                if (! empty($itemIds)) {
                    $query->orWhereIn('sem_item_id', $itemIds);
                }
            })
            ->select(['sem_target_id', 'sem_item_id'])
            ->get();

        $hasGroupHistory = $historyRecords->isNotEmpty();
        $hasTargetLevelHistory = $historyRecords->contains(fn ($r) => (int) $r->sem_target_id === $this->indicatorId);
        $historyItemIds = $historyRecords->pluck('sem_item_id')->filter()->map(fn ($id) => (int) $id)->unique()->all();

        $hasHistoryByItem = [];
        foreach ($this->rows as $row) {
            $itemId = (int) ($row['sem_item_id'] ?? 0);
            if ($itemId > 0) {
                $hasHistoryByItem[$itemId] = $hasTargetLevelHistory || in_array($itemId, $historyItemIds, true);
            }
        }

        $isTargetNewlyAdded = DB::table('ipc_sem_target_edit_histories')
            ->where('field_name', 'created')
            ->where('sem_target_id', $this->indicatorId)
            ->where(function ($q) {
                $q->whereNull('sem_item_id')->orWhere('sem_item_id', 0);
            })
            ->exists();

        $semesterId = (int) ($this->rows[0]['semester_id'] ?? 0);
        if ($semesterId <= 0) {
            $semesterId = (int) DB::table('ipc_sem_targets_indicator')
                ->where('id', $this->indicatorId)
                ->value('semester_id');
        }

        $isSemesterLocked = false;
        if ($semesterId > 0) {
            $semLock = DB::table('ipc_semester')->where('id', $semesterId)->value('lock');
            $isSemesterLocked = (int) $semLock === 1;
        }

        return view('livewire.semestral-target.indicator-rows', [
            'categories' => $categories,
            'hasGroupHistory' => $hasGroupHistory,
            'hasHistoryByItem' => $hasHistoryByItem,
            'isTargetNewlyAdded' => $isTargetNewlyAdded,
            'isSemesterLocked' => $isSemesterLocked,
        ]);
    }

    protected function normalizeTextareaValue(mixed $value): string
    {
        $text = html_entity_decode((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/<br\s*\/?>/i', "\n", $text) ?? '';
    }
}
