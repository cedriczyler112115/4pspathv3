<?php

namespace App\Livewire\HarmonizedIpc;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
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

    /** @var array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> */
    public array $editRows = [];

    /** @var array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> */
    public array $pendingSubTargets = [];

    #[Reactive]
    public string $positionFilter = '';

    /** @param array<int, array<string, mixed>> $rows */
    public function mount(int $indicatorId, array $rows, string $positionFilter = ''): void
    {
        $this->indicatorId = $indicatorId;
        $this->rows = $rows;
        $this->positionFilter = $positionFilter;
    }

    public function edit(): void
    {
        $firstRow = $this->rows[0] ?? null;

        if ($firstRow === null || (int) ($firstRow['target_status'] ?? 0) !== 1) {
            return;
        }

        $this->editing = true;
        $this->editActivity = $this->normalizeTextareaValue($firstRow['activity'] ?? '');
        $this->editCategory = (string) ($firstRow['kra_category'] ?? '');
        $this->editRows = collect($this->rows)->mapWithKeys(fn (array $row): array => [
            (int) $row['id'] => [
                'semester' => $this->normalizeSemesterValue($row['new_semester'] ?? null),
                'description' => $this->normalizeTextareaValue($row['description'] ?? ''),
                'efficiency' => $this->normalizeTextareaValue($row['rg_efficiency_'] ?? ''),
                'quality' => $this->normalizeTextareaValue($row['rg_quality_'] ?? ''),
                'timeliness' => $this->normalizeTextareaValue($row['rg_timeliness_'] ?? ''),
                'movs' => $this->normalizeTextareaValue($row['rg_mov_'] ?? ''),
                'remarks' => $this->normalizeTextareaValue($row['rg_remarks_'] ?? ''),
            ],
        ])->all();
    }

    public function cancel(): void
    {
        $this->resetValidation();
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

        $validated = $this->validate($this->saveRules());
        $ownedIndicator = DB::table('harmonized_ipc_targets_indicators')
            ->where('id', $this->indicatorId)
            ->where('target_status', 1)
            ->exists();

        if (! $ownedIndicator) {
            $this->cancel();

            return;
        }

        $allowedItemIds = collect($this->rows)->pluck('id')->map(fn (mixed $id): int => (int) $id);
        $submittedRows = collect($validated['editRows'] ?? [])
            ->filter(fn (array $values, int|string $id): bool => $allowedItemIds->contains((int) $id));

        DB::transaction(function () use ($submittedRows, $userId, $validated): void {
            if (! $this->creatingSubTarget) {
                foreach ($submittedRows as $itemId => $values) {
                    DB::table('harmonized_ipc_targets_indicators_itemlist')
                        ->where('id', (int) $itemId)
                        ->where('ind_id', $this->indicatorId)
                        ->update([
                            'new_semester' => (int) $values['semester'],
                            'description' => $values['description'],
                            'rg_efficiency_' => $values['efficiency'],
                            'rg_quality_' => $values['quality'],
                            'rg_timeliness_' => $values['timeliness'],
                            'rg_mov_' => $values['movs'],
                            'rg_remarks_' => $values['remarks'] ?? '',
                            'modified_by' => $userId,
                        ]);
                }

                DB::table('harmonized_ipc_targets_indicators')
                    ->where('id', $this->indicatorId)
                    ->update([
                        'activity' => $validated['editActivity'],
                        'kra_category' => $validated['editCategory'],
                    ]);
            }

            if ($this->creatingSubTarget) {
                foreach ($this->pendingSubTargets as $pendingSubTarget) {
                    $nextItemOrder = ((int) DB::table('harmonized_ipc_targets_indicators_itemlist')
                        ->where('ind_id', $this->indicatorId)
                        ->max('display_order')) + 1;

                    $insertedId = (int) DB::table('harmonized_ipc_targets_indicators_itemlist')->insertGetId([
                        'ind_id' => $this->indicatorId,
                        'display_order' => $nextItemOrder,
                        'new_semester' => $this->semesterToDatabaseValue($pendingSubTarget['semester'] ?? null),
                        'description' => $pendingSubTarget['description'] ?? '',
                        'rg_efficiency_' => $pendingSubTarget['efficiency'] ?? '',
                        'rg_quality_' => $pendingSubTarget['quality'] ?? '',
                        'rg_timeliness_' => $pendingSubTarget['timeliness'] ?? '',
                        'rg_mov_' => $pendingSubTarget['movs'] ?? '',
                        'rg_remarks_' => $pendingSubTarget['remarks'] ?? '',
                        'created_by' => $userId,
                        'modified_by' => $userId,
                        'indi_status' => 1,
                        'date_created' => now(),
                    ]);

                    if ($insertedId > 0) {
                        $newRow = DB::table('harmonized_ipc_targets_indicators_itemlist as itl')
                            ->leftJoin('harmonized_ipc_targets_indicators as iti', 'itl.ind_id', '=', 'iti.id')
                            ->where('itl.id', $insertedId)
                            ->select([
                                'itl.id',
                                'itl.ind_id',
                                'iti.target_group_id',
                                'iti.harmonized_position_id',
                                'iti.target_sem as target_sem_num',
                                DB::raw('(CASE WHEN iti.target_sem = 1 THEN "1st Semester" WHEN iti.target_sem = 2 THEN "2nd Semester" WHEN iti.target_sem = 3 THEN "Both Semester" END) as target_sem'),
                                'itl.new_semester',
                                'iti.target_year',
                                'iti.kra_category',
                                'iti.activity',
                                'iti.target_status',
                                'itl.date_created',
                                'itl.description',
                                'itl.rg_efficiency_',
                                'itl.rg_quality_',
                                'itl.rg_timeliness_',
                                'itl.rg_mov_',
                                'itl.rg_remarks_',
                                'itl.indi_status',
                            ])
                            ->first();

                        if ($newRow !== null) {
                            $this->rows[] = (array) $newRow;
                        }
                    }
                }
            }
        });

        foreach ($this->rows as &$row) {
            $values = $submittedRows->get((int) $row['id']);

            if ($values === null) {
                continue;
            }

            $row['activity'] = $validated['editActivity'];
            $row['kra_category'] = (int) $validated['editCategory'];
            $row['new_semester'] = (int) $values['semester'];
            $row['description'] = $values['description'];
            $row['rg_efficiency_'] = $values['efficiency'];
            $row['rg_quality_'] = $values['quality'];
            $row['rg_timeliness_'] = $values['timeliness'];
            $row['rg_mov_'] = $values['movs'];
            $row['rg_remarks_'] = $values['remarks'] ?? '';
        }
        unset($row);

        $this->cancel();
        Flux::toast(variant: 'success', text: __('Harmonized IPC updated.'));
    }

    public function requestAddSubTarget(): void
    {
        $firstRow = $this->rows[0] ?? null;

        if ($firstRow === null || (int) ($firstRow['target_status'] ?? 0) !== 1) {
            return;
        }

        $this->editing = true;
        $this->creatingSubTarget = true;
        $this->editRows = [];
        $this->pendingSubTargets[] = [
            'semester' => '',
            'description' => '',
            'efficiency' => '',
            'quality' => '',
            'timeliness' => '',
            'movs' => '',
            'remarks' => '',
        ];
    }

    public function requestDelete(): void
    {
        $rowId = (int) ($this->rows[0]['id'] ?? 0);

        if ($rowId > 0) {
            $this->dispatch('harmonized-ipc-delete-requested', rowId: $rowId);
        }
    }

    public function requestDeleteSubTarget(int $itemId): void
    {
        if ($itemId > 0) {
            $this->dispatch('harmonized-ipc-subtarget-delete-requested', itemId: $itemId);
        }
    }

    public function render(): View
    {
        return view('livewire.harmonized-ipc.indicator-rows', [
            'semesters' => collect([
                (object) ['value' => '1', 'label' => '1st Semester'],
                (object) ['value' => '2', 'label' => '2nd Semester'],
                (object) ['value' => '3', 'label' => 'Both Semester'],
            ]),
        ]);
    }

    /** @return array<string, array<int, string>> */
    protected function saveRules(): array
    {
        $rules = [
            'editActivity' => $this->creatingSubTarget ? ['nullable', 'string'] : ['required', 'string'],
            'editCategory' => $this->creatingSubTarget ? ['nullable', 'in:1,2,3'] : ['required', 'in:1,2,3'],
        ];

        if (! $this->creatingSubTarget) {
            $rules['editRows'] = ['required', 'array', 'min:1'];

            foreach (array_keys($this->editRows) as $itemId) {
                $prefix = 'editRows.'.$itemId.'.';
                $rules[$prefix.'semester'] = ['required', 'in:1,2,3'];
                $rules[$prefix.'description'] = ['required', 'string'];
                $rules[$prefix.'efficiency'] = ['required', 'string'];
                $rules[$prefix.'quality'] = ['required', 'string'];
                $rules[$prefix.'timeliness'] = ['required', 'string'];
                $rules[$prefix.'movs'] = ['required', 'string'];
                $rules[$prefix.'remarks'] = ['nullable', 'string'];
            }
        }

        foreach (array_keys($this->pendingSubTargets) as $itemId) {
            $prefix = 'pendingSubTargets.'.$itemId.'.';
            $rules[$prefix.'semester'] = ['required', 'in:1,2,3'];
            $rules[$prefix.'description'] = ['required', 'string'];
            $rules[$prefix.'efficiency'] = ['required', 'string'];
            $rules[$prefix.'quality'] = ['required', 'string'];
            $rules[$prefix.'timeliness'] = ['required', 'string'];
            $rules[$prefix.'movs'] = ['required', 'string'];
            $rules[$prefix.'remarks'] = ['nullable', 'string'];
        }

        return $rules;
    }

    protected function normalizeSemesterValue(mixed $value): string
    {
        $semester = trim((string) ($value ?? ''));

        return in_array($semester, ['1', '2', '3'], true) ? $semester : '';
    }

    protected function semesterToDatabaseValue(mixed $value): ?int
    {
        $semester = $this->normalizeSemesterValue($value);

        if ($semester === '') {
            return null;
        }

        return (int) $semester;
    }

    protected function normalizeTextareaValue(mixed $value): string
    {
        $text = html_entity_decode((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/<br\s*\/?>/i', "\n", $text) ?? '';
    }
}
