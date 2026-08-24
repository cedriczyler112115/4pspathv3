<?php

namespace App\Livewire\SemestralTarget;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
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

    /** @param array<int, array<string, mixed>> $rows */
    public function mount(int $indicatorId, array $rows): void
    {
        $this->indicatorId = $indicatorId;
        $this->rows = $rows;
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

        $this->editing = true;
        $this->creatingSubTarget = false;
        $this->editActivity = $this->normalizeTextareaValue($firstRow['activity'] ?? '');
        $this->editCategory = (string) ($firstRow['kra_category'] ?? '');
        $this->editRows = collect($this->rows)->mapWithKeys(fn (array $row): array => [
            (int) $row['sem_item_id'] => [
                'description' => $this->normalizeTextareaValue($row['description'] ?? ''),
                'quantity' => $this->normalizeTextareaValue($row['rg_quantity'] ?? ''),
                'quality' => $this->normalizeTextareaValue($row['rg_quality'] ?? ''),
                'timeliness' => $this->normalizeTextareaValue($row['rg_timeliness'] ?? ''),
                'movs' => $this->normalizeTextareaValue($row['rg_movs'] ?? ''),
                'remarks' => $this->normalizeTextareaValue($row['rg_remarks'] ?? ''),
            ],
        ])->all();
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

    public function render(): View
    {
        $includeStrategic = \App\Models\ApplicationSetting::boolean('include_strategic_function', true);

        $categories = collect([
            (object) ['value' => '1', 'label' => 'Strategic Function'],
            (object) ['value' => '2', 'label' => 'Core Function'],
            (object) ['value' => '3', 'label' => 'Support Function'],
        ])->when(! $includeStrategic, fn ($cats) => $cats->reject(fn ($c) => $c->value === '1')->values());

        return view('livewire.semestral-target.indicator-rows', [
            'categories' => $categories,
        ]);
    }

    protected function normalizeTextareaValue(mixed $value): string
    {
        $text = html_entity_decode((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/<br\s*\/?>/i', "\n", $text) ?? '';
    }
}
