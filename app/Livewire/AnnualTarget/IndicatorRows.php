<?php

namespace App\Livewire\AnnualTarget;

use Flux\Flux;
use Illuminate\Contracts\View\View;
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

    public string $editActivity = '';

    public string $editCategory = '';

    /** @var array<int, array{semester:string, description:string, efficiency:string, quality:string, timeliness:string, movs:string, remarks:string}> */
    public array $editRows = [];

    /** @param array<int, array<string, mixed>> $rows */
    public function mount(int $indicatorId, array $rows): void
    {
        $this->indicatorId = $indicatorId;
        $this->rows = $rows;
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
        $this->editActivity = '';
        $this->editCategory = '';
        $this->editRows = [];
    }

    public function save(): void
    {
        $userId = Auth::id();

        if (! $this->editing || $userId === null) {
            return;
        }

        $validated = $this->validate($this->saveRules());
        $ownedIndicator = DB::table('ipc_targets_indicators')
            ->where('id', $this->indicatorId)
            ->where('user_id', $userId)
            ->where('target_status', 1)
            ->exists();

        if (! $ownedIndicator) {
            $this->cancel();

            return;
        }

        $allowedItemIds = collect($this->rows)->pluck('id')->map(fn (mixed $id): int => (int) $id);
        $submittedRows = collect($validated['editRows'])
            ->filter(fn (array $values, int|string $id): bool => $allowedItemIds->contains((int) $id));

        DB::transaction(function () use ($submittedRows, $userId, $validated): void {
            foreach ($submittedRows as $itemId => $values) {
                DB::table('ipc_targets_indicators_itemlist')
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

            DB::table('ipc_targets_indicators')
                ->where('id', $this->indicatorId)
                ->where('user_id', $userId)
                ->update([
                    'activity' => $validated['editActivity'],
                    'kra_category' => $validated['editCategory'],
                ]);
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
        Flux::toast(variant: 'success', text: __('Annual target updated.'));
    }

    public function requestDelete(): void
    {
        $rowId = (int) ($this->rows[0]['id'] ?? 0);

        if ($rowId > 0) {
            $this->dispatch('annual-target-delete-requested', rowId: $rowId);
        }
    }

    public function render(): View
    {
        return view('livewire.annual-target.indicator-rows', [
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
            'editActivity' => ['required', 'string'],
            'editCategory' => ['required', 'in:1,2,3'],
            'editRows' => ['required', 'array', 'min:1'],
        ];

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

        return $rules;
    }

    protected function normalizeSemesterValue(mixed $value): string
    {
        $semester = trim((string) ($value ?? ''));

        return in_array($semester, ['1', '2', '3'], true) ? $semester : '';
    }

    protected function normalizeTextareaValue(mixed $value): string
    {
        $text = html_entity_decode((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/<br\s*\/?>/i', "\n", $text) ?? '';
    }
}
