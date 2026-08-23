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

    /** @param array<int, array<string, mixed>> $rows */
    public function mount(int $indicatorId, array $rows): void
    {
        $this->indicatorId = $indicatorId;
        $this->rows = $rows;
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
    }

    public function save(): void
    {
        $userId = Auth::id();

        if (! $this->editing || $userId === null) {
            return;
        }

        $nowManila = Carbon::now('Asia/Manila');

        DB::transaction(function () use ($userId, $nowManila): void {
            if (! $this->creatingSubTarget) {
                DB::table('ipc_sem_targets_indicator')
                    ->where('id', $this->indicatorId)
                    ->update([
                        'activity' => $this->editActivity,
                        'kra_category' => (int) $this->editCategory,
                        'modified_by' => $userId,
                        'last_date_modified' => $nowManila,
                    ]);

                foreach ($this->editRows as $itemId => $values) {
                    DB::table('ipc_sem_targets_indicator_itemlist')
                        ->where('id', (int) $itemId)
                        ->where('sem_target_id', $this->indicatorId)
                        ->update([
                            'description' => $values['description'] ?? '',
                            'rg_quantity' => $values['quantity'] ?? null,
                            'rg_quality' => $values['quality'] ?? null,
                            'rg_timeliness' => $values['timeliness'] ?? null,
                            'rg_movs' => $values['movs'] ?? null,
                            'rg_remarks' => $values['remarks'] ?? null,
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
        Flux::toast(variant: 'success', text: __('Semestral target updated successfully.'));
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
