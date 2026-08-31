<?php

namespace App\Livewire\Pages;

use App\Models\ApplicationSetting;
use App\Support\KraCategory;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Semestral Target')]
class SemestralTargetPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $fullName = '';
    public string $position = '';
    public string $designation = '';
    public string $divisionName = '';
    public string $sectionName = '';

    #[Url(as: 'sem_id')]
    public ?int $semId = null;

    public string $search = '';
    public string $categoryFilter = '';
    public string $targetStatusFilter = '';
    public int|string $perPage = 10;
    public bool $includeStrategicFunction = true;
    protected ?LengthAwarePaginator $semestralTargetsMemo = null;

    // Add Target Modal
    public bool $showAddModal = false;
    public ?int $addingKraCategory = null;
    public string $addActivity = '';
    public string $addDescription = '';
    public string $addEfficiency = '';
    public string $addQuality = '';
    public string $addTimeliness = '';
    public string $addMovs = '';
    public string $addRemarks = '';
    public string $addJustification = '';

    // Delete Modals
    public bool $showDeleteModal = false;
    public ?int $deletingSemTargetId = null;
    public string $deleteJustification = '';
    public bool $showDeleteSubTargetModal = false;
    public ?int $deletingSemItemId = null;

    // Recover Modal
    public bool $showRecoverModal = false;
    public array $deletedTargetsList = [];
    public string $deletedTargetSearch = '';
    public int $deletedTargetPage = 1;
    public int $deletedTargetPerPage = 8;

    // Lock/Unlock Confirm Modals
    public bool $showLockConfirmModal = false;
    public bool $showUnlockConfirmModal = false;
    public bool $showImReadyConfirmModal = false;
    public bool $showWaitingVerificationModal = false;

    // Areas of Improvement CRUD
    public bool $showAreasImprovementModal = false;
    public ?int $editingAreasImprovementId = null;
    public string $areasImprovement = '';
    public string $developmentActivities = '';
    public string $supportResources = '';
    public string $progressIntervention = '';

    // Documentation uploads
    /** @var array<int, UploadedFile> */
    public array $documentationUploads = [];
    public array $documentationFiles = [];
    public bool $showDocumentationPreviewModal = false;
    public ?array $previewDocumentationFile = null;

    // Show Edit History Modal
    public bool $showHistoryModal = false;
    public ?int $historyTargetId = null;
    public ?int $historyItemId = null;
    public array $historyRecords = [];

    // Copy Target Modal
    public bool $showCopyModal = false;
    public bool $showCopyAllConfirmModal = false;
    public string $copySourceYear = '';
    public string $copySourceCategory = '';
    public string $copySourceSemester = '';
    public string $copySourceStatusFilter = '';
    public string $copySourceSearch = '';
    public int $copyPage = 1;
    public int $copyPerPage = 5;

    public ?string $unauthorizedErrorMessage = null;
    public string $finalRating = '';
    public string $adjectivalRating = '';

    public function mount(): void
    {
        $this->includeStrategicFunction = ApplicationSetting::boolean('include_strategic_function', true);

        $this->categoryFilter = (string) Session::get($this->sessionKey('categoryFilter'), '');
        $savedTargetStatus = Session::get($this->sessionKey('targetStatusFilter'), '');
        if ($savedTargetStatus === '' && (bool) Session::get($this->sessionKey('hasCheckpointTarget'), false)) {
            $savedTargetStatus = 'checkpoint';
        }
        $this->targetStatusFilter = in_array($savedTargetStatus, ['checkpoint', 'incomplete'], true)
            ? $savedTargetStatus
            : '';
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->perPage = Session::get($this->sessionKey('perPage'), 10);
        if (is_string($this->perPage)) {
            $this->perPage = strtolower(trim($this->perPage));
        }

        if (! in_array($this->perPage, [10, 25, 50, 'all'], true)) {
            $this->perPage = 10;
        }

        if (!$this->includeStrategicFunction && $this->categoryFilter === '1') {
            $this->categoryFilter = '';
        }

        $this->validateSemId();
        $this->loadUserProfile();
        $this->calculateFinalRating(silent: true);
        $this->loadDocumentationFiles();

        if ($this->unauthorizedErrorMessage !== null) {
            Flux::toast(variant: 'danger', text: $this->unauthorizedErrorMessage);
        }
    }

    protected ?object $cachedSemRecord = null;

    protected function getSemesterRecord(): ?object
    {
        if (!$this->semId) {
            return null;
        }
        if ($this->cachedSemRecord === null) {
            $this->cachedSemRecord = DB::table('ipc_semester')->where('id', $this->semId)->first();
        }
        return $this->cachedSemRecord;
    }

    public function loadSemesterRatings(): void
    {
        if (!$this->semId) {
            $this->finalRating = '';
            $this->adjectivalRating = '';
            return;
        }

        $sem = $this->getSemesterRecord();
        if ($sem) {
            $this->finalRating = filled($sem->final_rating) ? $this->format5DecimalsWithoutRounding($sem->final_rating) : '0.00000';
            $this->adjectivalRating = filled($sem->adjectival_rating) ? (string) $sem->adjectival_rating : 'N/A';
        }
    }

    private function format5DecimalsWithoutRounding(mixed $val): string
    {
        $floatVal = (float) $val;
        if ($floatVal <= 0) {
            return '0.00000';
        }
        $str = (string) $floatVal;
        if (str_contains($str, '.')) {
            [$intPart, $decPart] = explode('.', $str, 2);
            return $intPart . '.' . substr(str_pad($decPart, 5, '0'), 0, 5);
        }
        return $str . '.00000';
    }

    #[On('semestral-target-updated')]
    public function calculateFinalRating(bool $silent = true): void
    {
        if (!$this->semId) {
            return;
        }

        $calcCatAvg = function (int $catId): float {
            $avg = DB::table('ipc_sem_targets_indicator_itemlist as stil')
                ->join('ipc_sem_targets_indicator as sti', 'stil.sem_target_id', '=', 'sti.id')
                ->where('sti.semester_id', $this->semId)
                ->where('sti.kra_category', $catId)
                ->whereNotNull('stil.average')
                ->where('stil.average', '!=', '')
                ->where('stil.average', '>', 0)
                ->avg('stil.average');

            return $avg !== null ? (float) $avg : 0.0;
        };

        $coreAvg = $calcCatAvg(2);
        $supportAvg = $calcCatAvg(3);

        if ($this->includeStrategicFunction) {
            $strategicAvg = $calcCatAvg(1);
            $finalVal = ($strategicAvg + $coreAvg + $supportAvg) / 3.0;
        } else {
            $finalVal = ($coreAvg + $supportAvg) / 2.0;
        }

        if ($finalVal > 0) {
            $finalStr = $this->format5DecimalsWithoutRounding($finalVal);
            $calcFinal = (float) $finalStr;
        } else {
            $calcFinal = 0.0;
            $finalStr = '0.00000';
        }

        $adjectival = match (true) {
            $calcFinal >= 5.00 => 'Outstanding',
            $calcFinal >= 4.00 => 'Very Satisfactory',
            $calcFinal >= 3.00 => 'Satisfactory',
            $calcFinal >= 2.00 => 'Unsatisfactory',
            $calcFinal > 0.00 => 'Poor',
            default => 'N/A',
        };

        $sem = $this->getSemesterRecord();
        $currFinal = $sem ? (string) ($sem->final_rating ?? '') : null;
        $currAdj = $sem ? (string) ($sem->adjectival_rating ?? '') : null;

        if ($currFinal !== $finalStr || $currAdj !== $adjectival) {
            DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->update([
                    'final_rating' => $finalStr,
                    'adjectival_rating' => $adjectival,
                ]);
            $this->cachedSemRecord = null;
        }

        $this->finalRating = $finalStr;
        $this->adjectivalRating = $adjectival;

        if (!$silent) {
            Flux::toast(
                variant: 'success',
                text: __('Final Rating calculated: :rating (:adjectival)', ['rating' => $finalStr, 'adjectival' => $adjectival])
            );
        }
    }

    public function getSemLockStatus(): int
    {
        $semRecord = $this->getSemesterRecord();
        return (int) ($semRecord->lock ?? 0);
    }

    public function openImReadyModal(): void
    {
        if (!$this->semId) {
            return;
        }

        if ($this->hasIncompleteSemestralTargets()) {
            Flux::toast(variant: 'warning', text: __('Please complete all targets, scores, accomplishments, and MOVs before indicating ready.'));
            return;
        }

        $this->showImReadyConfirmModal = true;
    }

    public function cancelImReadyConfirm(): void
    {
        $this->showImReadyConfirmModal = false;
    }

    public function confirmImReady(): void
    {
        $this->showImReadyConfirmModal = false;

        if (!$this->semId) {
            return;
        }

        DB::table('ipc_semester')
            ->where('id', $this->semId)
            ->update([
                'lock' => 2,
                'is_ready' => 1,
                'date_ready' => Carbon::now('Asia/Manila'),
            ]);

        $this->cachedSemRecord = null;
        $this->dispatch('semestral-target-updated');
        $this->dispatch('semestral-target-reload');

        Flux::toast(
            variant: 'success',
            text: __("You have indicated that you are ready! Your semestral targets are now locked from further editing.")
        );
    }

    public function openWaitingVerificationModal(): void
    {
        if (!$this->semId) {
            return;
        }

        $this->showWaitingVerificationModal = true;
    }

    public function cancelWaitingVerificationConfirm(): void
    {
        $this->showWaitingVerificationModal = false;
    }

    public function confirmWaitingVerification(): void
    {
        $this->showWaitingVerificationModal = false;

        if (!$this->semId) {
            return;
        }

        DB::table('ipc_semester')
            ->where('id', $this->semId)
            ->update([
                'lock' => 1,
            ]);

        $this->cachedSemRecord = null;
        $this->dispatch('semestral-target-updated');
        $this->dispatch('semestral-target-reload');

        Flux::toast(
            variant: 'success',
            text: __("Semestral target unlocked for editing. Status reverted back to rating mode.")
        );
    }

    public function areasImprovementItems(): Collection
    {
        if (!$this->semId) {
            return collect();
        }

        return DB::table('ipc_areas_improvement as ai')
            ->leftJoin('users as u', 'u.id', '=', 'ai.encoded_by')
            ->where('ai.semester_id', $this->semId)
            ->orderByDesc('ai.date_encoded')
            ->orderByDesc('ai.id')
            ->get([
                'ai.id',
                'ai.areas_improvement',
                'ai.development_activities',
                'ai.support_resources',
                'ai.progress_intervention',
                'ai.date_encoded',
                'ai.encoded_by',
                DB::raw('COALESCE(u.name, \'\') as encoded_by_name'),
            ]);
    }

    /**
     * @return array{activeRows: Collection<int, object>, deletedRows: Collection<int, object>}
     */
    public function checkpointChangeRows(): array
    {
        if (!$this->semId) {
            return [
                'activeRows' => collect(),
                'deletedRows' => collect(),
            ];
        }

        $activeTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $this->semId)
            ->pluck('id')
            ->all();

        $allTargetIds = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('ipc_sem_targets_indicator as sti', 'h.sem_target_id', '=', 'sti.id')
            ->where(function ($q) use ($activeTargetIds) {
                if (!empty($activeTargetIds)) {
                    $q->whereIn('h.sem_target_id', $activeTargetIds);
                }
                $q->orWhere('sti.semester_id', $this->semId);
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

        $checkpointRows = collect();
        foreach ($histories->groupBy('sem_target_id') as $semTargetId => $targetRecords) {
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

            $itemLevelRecords = $targetRecords->filter(fn ($r) => !empty($r->sem_item_id) && (int) $r->sem_item_id > 0);
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
                    foreach (['quantity' => 'rg_quantity', 'quality' => 'rg_quality', 'timeliness' => 'rg_timeliness', 'movs' => 'rg_movs', 'remarks' => 'rg_remarks'] as $prop => $fieldName) {
                        $current = $iFirstRec->{'current_'.$prop} ?? null;
                        if (filled($current)) {
                            $iFields[] = (object) [
                                'field_name' => $fieldName,
                                'field_label' => $fieldLabels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName)),
                                'order_rank' => $fieldOrder[$fieldName] ?? 99,
                                'old_value' => '-',
                                'new_value' => $current,
                            ];
                        }
                    }
                } elseif ($isSubItemDeleted) {
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

            $checkpointRows->push((object) [
                'sem_target_id' => $semTargetId,
                'activity_title' => $activityTitle,
                'is_new_target' => $isNewlyAdded && ! $isDeletedTarget,
                'is_deleted' => $isDeletedTarget,
                'target_fields' => $targetFields,
                'item_groups' => $itemGroups,
                'justification' => collect($justifications)->unique()->filter(fn ($j) => filled($j) && $j !== '-')->join('; ') ?: ($isDeletedTarget ? __('Target Deleted') : __('Target Entry / Update')),
            ]);
        }

        return [
            'activeRows' => $checkpointRows->filter(fn ($r) => !($r->is_deleted ?? false))->values(),
            'deletedRows' => $checkpointRows->filter(fn ($r) => ($r->is_deleted ?? false))->values(),
        ];
    }

    public function openAreasImprovementModal(?int $id = null): void
    {
        if (!$this->semId) {
            return;
        }

        $this->resetAreasImprovementForm();
        $this->editingAreasImprovementId = $id;

        if ($id !== null) {
            $item = DB::table('ipc_areas_improvement')->where('id', $id)->where('semester_id', $this->semId)->first();
            if ($item === null) {
                return;
            }

            $this->areasImprovement = (string) ($item->areas_improvement ?? '');
            $this->developmentActivities = (string) ($item->development_activities ?? '');
            $this->supportResources = (string) ($item->support_resources ?? '');
            $this->progressIntervention = (string) ($item->progress_intervention ?? '');
        }

        $this->showAreasImprovementModal = true;
    }

    public function cancelAreasImprovement(): void
    {
        $this->showAreasImprovementModal = false;
        $this->resetAreasImprovementForm();
    }

    protected function resetAreasImprovementForm(): void
    {
        $this->editingAreasImprovementId = null;
        $this->areasImprovement = '';
        $this->developmentActivities = '';
        $this->supportResources = '';
        $this->progressIntervention = '';
    }

    public function saveAreasImprovement(): void
    {
        if (!$this->semId) {
            return;
        }

        $validated = $this->validate([
            'areasImprovement' => ['required', 'string'],
            'developmentActivities' => ['required', 'string'],
            'supportResources' => ['nullable', 'string'],
            'progressIntervention' => ['nullable', 'string'],
        ]);

        $payload = [
            'semester_id' => $this->semId,
            'areas_improvement' => $validated['areasImprovement'],
            'development_activities' => $validated['developmentActivities'],
            'support_resources' => $validated['supportResources'] ?? null,
            'progress_intervention' => $validated['progressIntervention'] ?? null,
            'date_encoded' => Carbon::now('Asia/Manila'),
            'encoded_by' => (int) Auth::id(),
        ];

        if ($this->editingAreasImprovementId !== null) {
            DB::table('ipc_areas_improvement')
                ->where('id', $this->editingAreasImprovementId)
                ->where('semester_id', $this->semId)
                ->update($payload);
            Flux::toast(variant: 'success', text: __('Areas of improvement updated successfully.'));
        } else {
            DB::table('ipc_areas_improvement')->insert($payload);
            Flux::toast(variant: 'success', text: __('Areas of improvement saved successfully.'));
        }

        $this->cancelAreasImprovement();
        $this->dispatch('semestral-target-updated');
    }

    public function deleteAreasImprovement(int $id): void
    {
        if (!$this->semId) {
            return;
        }

        DB::table('ipc_areas_improvement')
            ->where('id', $id)
            ->where('semester_id', $this->semId)
            ->delete();

        Flux::toast(variant: 'success', text: __('Areas of improvement deleted successfully.'));
        $this->dispatch('semestral-target-updated');
    }

    public function imReady(): void
    {
        $this->openImReadyModal();
    }

    #[On('semestral-target-updated')]
    public function handleSemestralTargetUpdated(): void
    {
        // Re-renders page component when child components update semestral targets or attachments
    }

    public function loadDocumentationFiles(): void
    {
        $directory = public_path('documentation');
        File::ensureDirectoryExists($directory);

        $this->documentationFiles = collect(File::files($directory))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->map(function ($file) use ($directory) {
                $relativePath = 'documentation/' . $file->getFilename();
                $mime = File::mimeType($file->getPathname()) ?: 'application/octet-stream';
                $isImage = str_starts_with($mime, 'image/');
                $isPdf = $mime === 'application/pdf';
                $isVideo = str_starts_with($mime, 'video/');
                $isPresentation = in_array($mime, [
                    'application/vnd.ms-powerpoint',
                    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                    'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                ], true);
                $isWord = in_array($mime, [
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ], true);

                return [
                    'name' => $file->getFilename(),
                    'path' => $relativePath,
                    'url' => asset($relativePath),
                    'mime' => $mime,
                    'size' => $file->getSize(),
                    'modified_at' => Carbon::createFromTimestamp($file->getMTime())->setTimezone('Asia/Manila')->format('M d, Y h:i A'),
                    'type' => $isImage ? 'image' : ($isPdf ? 'pdf' : ($isVideo ? 'video' : ($isPresentation ? 'presentation' : ($isWord ? 'word' : 'other')))),
                ];
            })
            ->values()
            ->all();
    }

    public function updatedDocumentationUploads(): void
    {
        $this->validate([
            'documentationUploads' => ['required', 'array'],
            'documentationUploads.*' => [
                'file',
                'mimes:pdf,jpg,jpeg,png,gif,webp,bmp,svg,mp4,mov,avi,mkv,wmv,webm,m4v,ppt,pptx,doc,docx',
            ],
        ]);

        $destination = public_path('documentation');
        File::ensureDirectoryExists($destination);

        foreach ($this->documentationUploads as $upload) {
            if (!$upload instanceof UploadedFile) {
                continue;
            }

            $originalName = pathinfo($upload->getClientOriginalName(), PATHINFO_FILENAME);
            $safeBaseName = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $originalName) ?: 'document';
            $extension = strtolower($upload->getClientOriginalExtension());
            $fileName = $safeBaseName . '_' . Carbon::now('Asia/Manila')->format('YmdHis') . '_' . uniqid() . '.' . $extension;

            $sourcePath = $upload->getRealPath();
            if ($sourcePath === false || !is_file($sourcePath)) {
                continue;
            }

            File::copy($sourcePath, $destination . DIRECTORY_SEPARATOR . $fileName);
        }

        $this->documentationUploads = [];
        $this->loadDocumentationFiles();
        Flux::toast(variant: 'success', text: __('Documentation files uploaded successfully.'));
    }

    public function openDocumentationPreview(string $fileName): void
    {
        $file = collect($this->documentationFiles)->firstWhere('name', $fileName);
        if (!$file) {
            return;
        }

        $this->previewDocumentationFile = $file;
        $this->showDocumentationPreviewModal = true;
    }

    public function closeDocumentationPreview(): void
    {
        $this->showDocumentationPreviewModal = false;
        $this->previewDocumentationFile = null;
    }

    public function deleteDocumentationFile(): void
    {
        if (!$this->previewDocumentationFile) {
            return;
        }

        $relativePath = (string) ($this->previewDocumentationFile['path'] ?? '');
        $absolutePath = public_path($relativePath);

        if ($relativePath !== '' && File::exists($absolutePath)) {
            File::delete($absolutePath);
        }

        $deletedName = (string) ($this->previewDocumentationFile['name'] ?? '');

        $this->closeDocumentationPreview();
        $this->loadDocumentationFiles();

        Flux::toast(variant: 'success', text: __('Documentation file deleted successfully.'));

        if ($deletedName !== '') {
            $this->dispatch('documentation-file-deleted', name: $deletedName);
        }
    }

    protected function validateSemId(): void
    {
        $userId = Auth::id();

        if ($this->semId !== null && $this->semId > 0 && $userId !== null) {
            $semRecord = $this->getSemesterRecord();

            if ($semRecord === null) {
                $this->unauthorizedErrorMessage = __('The requested semestral target record (ID: :id) does not exist.', ['id' => $this->semId]);
            } elseif ((int) $semRecord->user_id !== (int) $userId) {
                $this->unauthorizedErrorMessage = __('Unauthorized access: The requested semestral target record does not belong to your account.');
            }
        }
    }

    protected function loadUserProfile(): void
    {
        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        $user = DB::table('users')
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

        if ($user === null) {
            return;
        }

        $this->fullName = trim(($user->last_name ?? '') . (filled($user->last_name) ? ', ' : '') . collect([$user->first_name, $user->middle_name])->filter()->join(' '));
        $this->position = (string) ($user->position ?? '');
        $this->designation = (string) ($user->designation ?? '');
        $this->divisionName = (string) ($user->division_name ?? '');
        $this->sectionName = (string) ($user->section_name ?? '');
    }

    protected function sessionKey(string $key): string
    {
        return 'semestral_target_page_' . $key;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('search'), $this->search);
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('categoryFilter'), $this->categoryFilter);
    }

    public function updatedTargetStatusFilter(): void
    {
        if (!in_array($this->targetStatusFilter, ['', 'checkpoint', 'incomplete'], true)) {
            $this->targetStatusFilter = '';
        }

        $this->resetPage();
        Session::put($this->sessionKey('targetStatusFilter'), $this->targetStatusFilter);
        Session::forget($this->sessionKey('hasCheckpointTarget'));
    }

    public function updatedPerPage(): void
    {
        if (is_string($this->perPage)) {
            $this->perPage = strtolower(trim($this->perPage));
        }

        if (!in_array($this->perPage, [10, 25, 50, 'all'], true)) {
            $this->perPage = 10;
        }
        $this->resetPage();
        Session::put($this->sessionKey('perPage'), $this->perPage);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->targetStatusFilter = '';
        $this->perPage = 10;
        Session::forget([
            $this->sessionKey('search'),
            $this->sessionKey('categoryFilter'),
            $this->sessionKey('hasCheckpointTarget'),
            $this->sessionKey('targetStatusFilter'),
            $this->sessionKey('perPage'),
        ]);

        $this->resetPage();
    }

    public function printIpcrf(): void
    {
        if (! $this->semId) {
            Flux::toast(variant: 'danger', text: __('No semestral target record selected.'));
            return;
        }

        $url = route('myratings.semestral-target.print-ipcrf', ['sem_id' => $this->semId]);
        $this->dispatch('open-new-tab', url: $url);
    }

    public function printCheckpoint(): void
    {
        if (!$this->semId) {
            Flux::toast(variant: 'danger', text: __('No semestral target record selected.'));

            return;
        }

        $url = route('myratings.semestral-target.print-checkpoint', ['sem_id' => $this->semId]);
        $this->dispatch('open-new-tab', url: $url);
    }

    /**
     * @return Collection<int, object>
     */
    public function categories(): Collection
    {
        return collect([
            (object) ['value' => '1', 'label' => __('Strategic Function')],
            (object) ['value' => '2', 'label' => __('Core Function')],
            (object) ['value' => '3', 'label' => __('Support Function')],
        ])->when(!$this->includeStrategicFunction, fn(Collection $categories): Collection => $categories
                ->reject(fn(object $category): bool => $category->value === '1')
                ->values());
    }

    /**
     * @return array<int, object{value: int, label: string}>
     */
    public function perPageOptions(): array
    {
        return [
            (object) ['value' => 10, 'label' => '10'],
            (object) ['value' => 25, 'label' => '25'],
            (object) ['value' => 50, 'label' => '50'],
            (object) ['value' => 'all', 'label' => __('ALL')],
        ];
    }

    public function isAllPerPage(): bool
    {
        return $this->perPage === 'all';
    }

    /** @return array<int, int|string> */
    public function paginationElements(LengthAwarePaginator $paginator): array
    {
        $lastPage = $paginator->lastPage();

        if ($lastPage <= 7) {
            return range(1, $lastPage);
        }

        $currentPage = $paginator->currentPage();

        if ($currentPage <= 4) {
            return [1, 2, 3, 4, 5, 'end-ellipsis', $lastPage];
        }

        if ($currentPage >= $lastPage - 3) {
            return [1, 'start-ellipsis', $lastPage - 4, $lastPage - 3, $lastPage - 2, $lastPage - 1, $lastPage];
        }

        return [1, 'start-ellipsis', $currentPage - 1, $currentPage, $currentPage + 1, 'end-ellipsis', $lastPage];
    }

    public function semesterHeading(): string
    {
        $userId = Auth::id();
        $semRecord = null;

        if ($this->semId !== null && $this->semId > 0) {
            $semRecord = DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->where('user_id', $userId)
                ->select(['semester', 'year'])
                ->first();
        }

        if ($semRecord === null) {
            $semRecord = DB::table('ipc_sem_targets_indicator as sti')
                ->join('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
                ->where('sem.user_id', $userId)
                ->select(['sem.semester', 'sem.year'])
                ->first();
        }

        $semInt = (int) ($semRecord->semester ?? 0);
        $year = $semRecord->year ?? null;

        if ($semInt === 1) {
            return $year ? __('Semestral Target for 1st Semester of :year', ['year' => $year]) : __('Semestral Target for 1st Semester');
        }
        if ($semInt === 2) {
            return $year ? __('Semestral Target for 2nd Semester of :year', ['year' => $year]) : __('Semestral Target for 2nd Semester');
        }

        return $year ? __('Semestral Target of :year', ['year' => $year]) : __('Semestral Target');
    }

    public function activeSemesterId(): ?int
    {
        if ($this->unauthorizedErrorMessage !== null) {
            return null;
        }

        if ($this->semId !== null && $this->semId > 0) {
            $isOwner = DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->where('user_id', Auth::id())
                ->exists();

            if ($isOwner) {
                return $this->semId;
            }

            return null;
        }

        $firstSem = DB::table('ipc_semester')
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->value('id');

        return $firstSem ? (int) $firstSem : null;
    }

    #[On('semestral-target-updated')]
    public function refreshTargets(): void
    {
        $this->flushSemestralTargetCaches();
        // Triggers re-render to instantly move targets to their new KRA category sections
    }

    // Add Target Methods
    #[On('open-add-target-modal')]
    public function handleOpenAddTargetModal(mixed $kraCategory = null, mixed $payload = null): void
    {
        $category = 1;
        if (is_numeric($kraCategory)) {
            $category = (int) $kraCategory;
        } elseif (is_array($kraCategory)) {
            $category = (int) ($kraCategory['kraCategory'] ?? $kraCategory['kra'] ?? $kraCategory['category'] ?? 1);
        } elseif (is_array($payload)) {
            $category = (int) ($payload['kraCategory'] ?? $payload['kra'] ?? $payload['category'] ?? 1);
        } elseif (is_numeric($payload)) {
            $category = (int) $payload;
        }

        $this->openAddTargetModal($category);
    }

    public function openAddModal(int $kraCategory): void
    {
        $this->openAddTargetModal($kraCategory);
    }

    public function is2026SecondSemesterOrBeyond(): bool
    {
        $semId = $this->semId ?: $this->activeSemesterId();

        if (!$semId) {
            return false;
        }

        $semRecord = DB::table('ipc_semester')->where('id', $semId)->first();
        if (!$semRecord) {
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

    public function openAddTargetModal(int $kraCategory): void
    {
        if (! $this->canModifyActiveSemester()) {
            return;
        }

        $this->resetValidation();
        $this->addingKraCategory = $kraCategory;
        $this->addActivity = '';
        $this->addDescription = '';
        $this->addEfficiency = '';
        $this->addQuality = '';
        $this->addTimeliness = '';
        $this->addMovs = '';
        $this->addRemarks = '';
        $this->addJustification = '';
        $this->showAddModal = true;
    }

    public function cancelAdd(): void
    {
        $this->resetValidation();
        $this->showAddModal = false;
        $this->addingKraCategory = null;
        $this->addActivity = '';
        $this->addDescription = '';
        $this->addEfficiency = '';
        $this->addQuality = '';
        $this->addTimeliness = '';
        $this->addMovs = '';
        $this->addRemarks = '';
        $this->addJustification = '';
    }

    public function saveAdd(): void
    {
        $ipcSemesterId = $this->activeSemesterId();
        $userId = Auth::id();

        if (!$ipcSemesterId || !$userId || !$this->addingKraCategory) {
            Flux::toast(variant: 'danger', text: __('Unable to add target. Semester not found.'));
            return;
        }

        if (! $this->canModifyActiveSemester()) {
            $this->cancelAdd();

            return;
        }

        $is2026Sem2 = $this->is2026SecondSemesterOrBeyond();

        $rules = [
            'addActivity' => ['required', 'string'],
            'addDescription' => ['required', 'string'],
            'addEfficiency' => ['required', 'string'],
            'addQuality' => ['required', 'string'],
            'addTimeliness' => ['required', 'string'],
            'addMovs' => ['required', 'string'],
            'addRemarks' => ['nullable', 'string'],
            'addJustification' => $is2026Sem2 ? ['required', 'string'] : ['nullable', 'string'],
        ];

        $messages = [
            'addActivity.required' => __('Key Result Area is required.'),
            'addDescription.required' => __('Success Indicator is required.'),
            'addEfficiency.required' => __('Efficiency is required.'),
            'addQuality.required' => __('Quality is required.'),
            'addTimeliness.required' => __('Timeliness is required.'),
            'addMovs.required' => __('Means of Verification (MOVs) is required.'),
            'addJustification.required' => __('Justification is required.'),
        ];

        $this->validate($rules, $messages);

        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($ipcSemesterId, $userId, $nowManila): void {
            $maxOrder = DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $ipcSemesterId)
                ->where('kra_category', $this->addingKraCategory)
                ->max('display_order');

            $semTargetId = (int) DB::table('ipc_sem_targets_indicator')->insertGetId([
                'ipc_target_indicator_id' => 0,
                'semester_id' => $ipcSemesterId,
                'kra_category' => $this->addingKraCategory,
                'display_order' => ((int) $maxOrder) + 1,
                'activity' => $this->addActivity,
                'verified' => null,
                'verified_by' => null,
                'date_verified' => null,
                'remarks' => null,
                'target_status' => 1,
                'created_by' => $userId,
                'date_created' => $nowManila,
                'modified_by' => $userId,
                'last_date_modified' => $nowManila,
                'target_from' => $userId,
            ]);

            $semItemId = (int) DB::table('ipc_sem_targets_indicator_itemlist')->insertGetId([
                'target_orig_id' => 0,
                'sem_target_id' => $semTargetId,
                'display_order' => 1,
                'sem_item_id' => $ipcSemesterId,
                'new_semester' => null,
                'description' => $this->addDescription,
                'actual_accomp' => null,
                'weight' => null,
                'quantity' => null,
                'quality' => null,
                'timeliness' => null,
                'rg_quantity' => $this->addEfficiency,
                'rg_quality' => $this->addQuality,
                'rg_timeliness' => $this->addTimeliness,
                'rg_movs' => $this->addMovs,
                'rg_remarks' => $this->addRemarks,
                'remarks' => 1,
                'created_by' => $userId,
                'date_created' => $nowManila,
                'modified_by' => $userId,
                'date_modified' => $nowManila,
            ]);

            $justificationToSave = filled($this->addJustification) ? $this->addJustification : 'Target Added';

            $this->logSemTargetHistory($semTargetId, $semItemId, 'activity', null, $this->addActivity, $userId, $nowManila, $justificationToSave);
            $this->logSemTargetHistory($semTargetId, $semItemId, 'description', null, $this->addDescription, $userId, $nowManila, $justificationToSave);
            if (!blank($this->addEfficiency)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_quantity', null, $this->addEfficiency, $userId, $nowManila, $justificationToSave);
            }
            if (!blank($this->addQuality)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_quality', null, $this->addQuality, $userId, $nowManila, $justificationToSave);
            }
            if (!blank($this->addTimeliness)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_timeliness', null, $this->addTimeliness, $userId, $nowManila, $justificationToSave);
            }
            if (!blank($this->addMovs)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_movs', null, $this->addMovs, $userId, $nowManila, $justificationToSave);
            }
            if (!blank($this->addRemarks)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_remarks', null, $this->addRemarks, $userId, $nowManila, $justificationToSave);
            }
            $this->logSemTargetHistory($semTargetId, $semItemId, 'created', null, 'Target Created', $userId, $nowManila, $justificationToSave);
        });

        $this->cancelAdd();
        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Semestral target added successfully.'));
    }

    // Delete Target Events & Methods
    #[On('semestral-target-delete-requested')]
    public function requestDeleteTarget(int $semTargetId): void
    {
        if (! $this->canModifyActiveSemester($semTargetId)) {
            return;
        }

        $this->resetValidation();
        $this->deletingSemTargetId = $semTargetId;
        $this->deleteJustification = '';
        $this->showDeleteModal = true;
    }

    public function cancelDeleteTarget(): void
    {
        $this->resetValidation();
        $this->showDeleteModal = false;
        $this->deletingSemTargetId = null;
        $this->deleteJustification = '';
    }

    public function confirmDeleteTarget(): void
    {
        if ($this->deletingSemTargetId === null) {
            return;
        }

        $semTargetId = $this->deletingSemTargetId;

        if (! $this->canModifyActiveSemester($semTargetId)) {
            $this->cancelDeleteTarget();

            return;
        }

        $is2026Sem2 = $this->is2026SecondSemesterOrBeyond();

        if ($is2026Sem2) {
            $this->validate(
                ['deleteJustification' => ['required', 'string']],
                ['deleteJustification.required' => __('Justification is required for deleting a target.')]
            );
        }

        $target = DB::table('ipc_sem_targets_indicator')->where('id', $semTargetId)->first();
        if (!$target) {
            $this->cancelDeleteTarget();
            return;
        }

        $items = DB::table('ipc_sem_targets_indicator_itemlist')->where('sem_target_id', $semTargetId)->get();
        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');
        $userId = Auth::id() ?: $target->created_by;
        $justificationToSave = filled($this->deleteJustification) ? $this->deleteJustification : 'Target Deleted';

        DB::transaction(function () use ($semTargetId, $target, $items, $nowManila, $userId, $justificationToSave): void {
            // Log target-level fields in history even if it had no prior history entries
            $this->logSemTargetHistory($semTargetId, null, 'activity', $target->activity, 'For Deletion', $userId, $nowManila, $justificationToSave);
            $this->logSemTargetHistory($semTargetId, null, 'kra_category', (string) $target->kra_category, 'For Deletion', $userId, $nowManila, $justificationToSave);

            // Log item-level fields in history
            foreach ($items as $item) {
                $itemId = (int) $item->id;
                $this->logSemTargetHistory($semTargetId, $itemId, 'description', $item->description, 'For Deletion', $userId, $nowManila, $justificationToSave);
                if (filled($item->rg_quantity)) {
                    $this->logSemTargetHistory($semTargetId, $itemId, 'rg_quantity', $item->rg_quantity, 'For Deletion', $userId, $nowManila, $justificationToSave);
                }
                if (filled($item->rg_quality)) {
                    $this->logSemTargetHistory($semTargetId, $itemId, 'rg_quality', $item->rg_quality, 'For Deletion', $userId, $nowManila, $justificationToSave);
                }
                if (filled($item->rg_timeliness)) {
                    $this->logSemTargetHistory($semTargetId, $itemId, 'rg_timeliness', $item->rg_timeliness, 'For Deletion', $userId, $nowManila, $justificationToSave);
                }
                if (filled($item->rg_movs)) {
                    $this->logSemTargetHistory($semTargetId, $itemId, 'rg_movs', $item->rg_movs, 'For Deletion', $userId, $nowManila, $justificationToSave);
                }
                if (filled($item->rg_remarks)) {
                    $this->logSemTargetHistory($semTargetId, $itemId, 'rg_remarks', $item->rg_remarks, 'For Deletion', $userId, $nowManila, $justificationToSave);
                }
            }

            // Log overall deleted entry
            $this->logSemTargetHistory($semTargetId, null, 'deleted', $target->activity, 'For Deletion', $userId, $nowManila, $justificationToSave);

            // Execute deletion in target indicator and itemlist tables (leaving edit history intact for recovery)
            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('sem_target_id', $semTargetId)
                ->delete();

            DB::table('ipc_sem_targets_indicator')
                ->where('id', $semTargetId)
                ->delete();
        });

        $this->cancelDeleteTarget();
        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Semestral target deleted and archived in history.'));
    }

    public function openRecoverModal(): void
    {
        $this->deletedTargetsList = $this->deletedTargetsData()->items();
        $this->showRecoverModal = true;
    }

    public function updatedDeletedTargetSearch(): void
    {
        $this->deletedTargetPage = 1;
    }

    public function setDeletedTargetPage(int $page): void
    {
        $this->deletedTargetPage = max(1, $page);
    }

    public function nextDeletedTargetPage(): void
    {
        $this->deletedTargetPage++;
    }

    public function previousDeletedTargetPage(): void
    {
        $this->deletedTargetPage = max(1, $this->deletedTargetPage - 1);
    }

    public function deletedTargetsData(): LengthAwarePaginator
    {
        $semId = $this->activeSemesterId();
        if (! $semId) {
            return new LengthAwarePaginator([], 0, $this->deletedTargetPerPage, $this->deletedTargetPage, [
                'pageName' => 'deletedTargetPage',
            ]);
        }

        $search = trim($this->deletedTargetSearch);

        $query = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('users as u', 'h.user_id', '=', 'u.id')
            ->where('h.field_name', 'deleted')
            ->where('h.sem_target_id', '>', 0)
            ->select([
                'h.id',
                'h.sem_target_id',
                'h.sem_item_id',
                'h.original_value',
                'h.old_value',
                'h.new_value',
                'h.justification',
                'h.date_created',
                'h.user_id',
                'u.first_name',
                'u.last_name',
            ])
            ->when($search !== '', function ($q) use ($search): void {
                $like = '%' . $search . '%';
                $q->where(function ($sub) use ($like): void {
                    $sub->where('h.original_value', 'like', $like)
                        ->orWhere('h.old_value', 'like', $like)
                        ->orWhere('h.justification', 'like', $like)
                        ->orWhere('u.first_name', 'like', $like)
                        ->orWhere('u.last_name', 'like', $like);
                });
            })
            ->orderByDesc('h.id');

        $rows = $query->get();
        $activeTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $semId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $activeItemIds = DB::table('ipc_sem_targets_indicator_itemlist')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $deletedList = [];

        foreach ($rows as $delEvent) {
            $targetId = (int) $delEvent->sem_target_id;
            $itemId = (int) $delEvent->sem_item_id;

            $isTargetActive = in_array($targetId, $activeTargetIds, true);
            $isItemActive = $itemId > 0 && in_array($itemId, $activeItemIds, true);
            if ($isTargetActive && ($itemId === 0 || $isItemActive)) {
                continue;
            }

            $targetHistories = DB::table('ipc_sem_target_edit_histories')
                ->where('sem_target_id', $targetId)
                ->orderBy('id', 'desc')
                ->get();

            $actRec = $targetHistories->firstWhere('field_name', 'activity');
            $kraRec = $targetHistories->firstWhere('field_name', 'kra_category');
            $descRec = $itemId > 0
                ? $targetHistories->where('sem_item_id', $itemId)->firstWhere('field_name', 'description')
                : $targetHistories->firstWhere('field_name', 'description');

            $activity = $actRec ? ($actRec->old_value ?: ($actRec->original_value ?: '-')) : ($delEvent->original_value ?: ($delEvent->old_value ?: 'Deleted Target'));
            $kraCategory = $kraRec ? (int) ($kraRec->old_value ?: ($kraRec->original_value ?: 1)) : 1;
            $description = $descRec ? ($descRec->old_value ?: ($descRec->original_value ?: '')) : '';

            $userName = trim(($delEvent->first_name ?? '') . ' ' . ($delEvent->last_name ?? ''));
            $dateFormatted = $delEvent->date_created ? Carbon::parse($delEvent->date_created)->format('M d, Y h:i A') : '-';

            $deletedList[] = [
                'sem_target_id' => $targetId,
                'kra_category' => $kraCategory,
                'kra_category_label' => KraCategory::label($kraCategory),
                'activity' => $activity,
                'description' => $description,
                'justification' => $delEvent->justification ?: '-',
                'deleted_at' => $dateFormatted,
                'user_name' => $userName !== '' ? $userName : 'System',
            ];
        }

        $deletedList = collect($deletedList)->unique('sem_target_id')->values();

        return new LengthAwarePaginator(
            $deletedList->forPage($this->deletedTargetPage, $this->deletedTargetPerPage)->values(),
            $deletedList->count(),
            $this->deletedTargetPerPage,
            $this->deletedTargetPage,
            ['pageName' => 'deletedTargetPage']
        );
    }

    public function recoverTarget(int $semTargetId): void
    {
        $semId = $this->activeSemesterId();
        $userId = Auth::id();

        if (!$semId || !$userId) {
            Flux::toast(variant: 'danger', text: __('Unable to recover target. Semester or user invalid.'));
            return;
        }

        $targetHistories = DB::table('ipc_sem_target_edit_histories')
            ->where('sem_target_id', $semTargetId)
            ->orderBy('id', 'asc')
            ->get();

        if ($targetHistories->isEmpty()) {
            Flux::toast(variant: 'danger', text: __('Target history not found for recovery.'));
            return;
        }

        $actRec = $targetHistories->firstWhere('field_name', 'activity');
        $kraRec = $targetHistories->firstWhere('field_name', 'kra_category');

        $activity = $actRec ? ($actRec->old_value ?: ($actRec->original_value ?: 'Restored Target')) : 'Restored Target';
        $kraCategory = $kraRec ? (int) ($kraRec->old_value ?: ($kraRec->original_value ?: 1)) : 1;

        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($semTargetId, $semId, $kraCategory, $activity, $targetHistories, $userId, $nowManila): void {
            $maxOrder = DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $semId)
                ->where('kra_category', $kraCategory)
                ->max('display_order');

            DB::table('ipc_sem_targets_indicator')->insert([
                'id' => $semTargetId,
                'ipc_target_indicator_id' => 0,
                'semester_id' => $semId,
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

            $itemGrouped = $targetHistories->where('sem_item_id', '>', 0)->groupBy('sem_item_id');

            if ($itemGrouped->isNotEmpty()) {
                $itemDisplayOrder = 1;
                foreach ($itemGrouped as $itemId => $iRecords) {
                    $descRec = $iRecords->firstWhere('field_name', 'description');
                    $qtyRec = $iRecords->firstWhere('field_name', 'rg_quantity');
                    $qualRec = $iRecords->firstWhere('field_name', 'rg_quality');
                    $timeRec = $iRecords->firstWhere('field_name', 'rg_timeliness');
                    $movsRec = $iRecords->firstWhere('field_name', 'rg_movs');
                    $remRec = $iRecords->firstWhere('field_name', 'rg_remarks');

                    DB::table('ipc_sem_targets_indicator_itemlist')->insert([
                        'id' => (int) $itemId,
                        'target_orig_id' => 0,
                        'sem_target_id' => $semTargetId,
                        'display_order' => $itemDisplayOrder++,
                        'sem_item_id' => $semId,
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

            // Delete all entries in history for restored target
            DB::table('ipc_sem_target_edit_histories')
                ->where('sem_target_id', $semTargetId)
                ->delete();
        });

        $this->openRecoverModal();
        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Semestral target restored successfully.'));
    }

    // Delete Sub-Target Events & Methods
    #[On('semestral-target-subtarget-delete-requested')]
    public function requestDeleteSubTarget(int $semItemId): void
    {
        if (! $this->canModifyActiveSemester(null, $semItemId)) {
            return;
        }

        $this->deleteJustification = '';
        $this->resetErrorBag('deleteJustification');
        $this->deletingSemItemId = $semItemId;
        $this->showDeleteSubTargetModal = true;
    }

    public function cancelDeleteSubTarget(): void
    {
        $this->showDeleteSubTargetModal = false;
        $this->deletingSemItemId = null;
        $this->deleteJustification = '';
        $this->resetErrorBag('deleteJustification');
    }

    public function confirmDeleteSubTarget(): void
    {
        if ($this->deletingSemItemId === null) {
            return;
        }

        if (! $this->canModifyActiveSemester(null, $this->deletingSemItemId)) {
            $this->cancelDeleteSubTarget();

            return;
        }

        if ($this->is2026SecondSemesterOrBeyond()) {
            $this->validate(
                ['deleteJustification' => 'required|string|min:3'],
                ['deleteJustification.required' => __('Justification is required for deleting a sub-target.')]
            );
        }

        $semItemId = (int) $this->deletingSemItemId;
        $item = DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $semItemId)->first();

        if (!$item) {
            $this->cancelDeleteSubTarget();
            return;
        }

        $semTargetId = (int) $item->sem_target_id;
        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');
        $userId = Auth::id() ?: ($item->created_by ?? 0);
        $justificationToSave = filled($this->deleteJustification) ? $this->deleteJustification : 'Sub-Target Deleted';

        DB::transaction(function () use ($semTargetId, $semItemId, $item, $nowManila, $userId, $justificationToSave): void {
            // Log sub-target item fields in history before deletion
            $this->logSemTargetHistory($semTargetId, $semItemId, 'description', $item->description, 'For Deletion', $userId, $nowManila, $justificationToSave);
            if (filled($item->rg_quantity)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_quantity', $item->rg_quantity, 'For Deletion', $userId, $nowManila, $justificationToSave);
            }
            if (filled($item->rg_quality)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_quality', $item->rg_quality, 'For Deletion', $userId, $nowManila, $justificationToSave);
            }
            if (filled($item->rg_timeliness)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_timeliness', $item->rg_timeliness, 'For Deletion', $userId, $nowManila, $justificationToSave);
            }
            if (filled($item->rg_movs)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_movs', $item->rg_movs, 'For Deletion', $userId, $nowManila, $justificationToSave);
            }
            if (filled($item->rg_remarks)) {
                $this->logSemTargetHistory($semTargetId, $semItemId, 'rg_remarks', $item->rg_remarks, 'For Deletion', $userId, $nowManila, $justificationToSave);
            }

            // Log overall deleted entry for sub-target
            $this->logSemTargetHistory($semTargetId, $semItemId, 'deleted', $item->description ?: 'Sub-Target Deleted', 'For Deletion', $userId, $nowManila, $justificationToSave);

            // Delete item from itemlist
            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $semItemId)
                ->delete();
        });

        $this->cancelDeleteSubTarget();
        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Sub-target deleted successfully.'));
    }

    #[On('show-semestral-target-edit-history')]
    public function openEditHistory(?int $itemId = null, ?int $indicatorId = null): void
    {
        $this->historyItemId = $itemId;
        $this->historyTargetId = $indicatorId;

        $query = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('users as u', 'h.user_id', '=', 'u.id')
            ->select([
                'h.id',
                'h.sem_target_id',
                'h.sem_item_id',
                'h.field_name',
                'h.original_value',
                'h.old_value',
                'h.new_value',
                'h.last_edited_value',
                'h.justification',
                'h.date_created',
                'h.created_at',
                'u.first_name',
                'u.last_name',
            ])
            ->orderBy('h.id', 'desc');

        if ($itemId && $itemId > 0) {
            $tIdLookup = $indicatorId ?: (int) DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $itemId)->value('sem_target_id');
            $query->where(function ($q) use ($itemId, $tIdLookup) {
                $q->where('h.sem_item_id', $itemId);
                if ($tIdLookup > 0) {
                    $q->orWhere(function ($q2) use ($tIdLookup) {
                        $q2->where('h.sem_target_id', $tIdLookup)
                            ->where('h.field_name', 'activity');
                    });
                }
            });
        } elseif ($indicatorId && $indicatorId > 0) {
            $query->where('h.sem_target_id', $indicatorId);
        } else {
            $this->historyRecords = [];
            $this->showHistoryModal = true;
            return;
        }

        $rawRecords = $query->get();

        $fieldLabels = [
            'activity' => 'Key Result Area',
            'description' => 'Success Indicator',
            'rg_quantity' => 'Efficiency',
            'rg_quality' => 'Quality',
            'rg_timeliness' => 'Timeliness',
            'rg_movs' => 'MOVs',
            'rg_remarks' => 'Remarks',
        ];

        $fieldOrder = [
            'activity' => 1,
            'description' => 2,
            'rg_quantity' => 3,
            'rg_quality' => 4,
            'rg_timeliness' => 5,
            'rg_movs' => 6,
            'rg_remarks' => 7,
        ];

        $historyItemIds = $rawRecords->pluck('sem_item_id')->filter()->map(fn($id) => (int) $id)->unique()->values()->all();

        $itemDescriptions = [];
        if (!empty($historyItemIds)) {
            $itemDescriptions = DB::table('ipc_sem_targets_indicator_itemlist')
                ->whereIn('id', $historyItemIds)
                ->pluck('description', 'id')
                ->toArray();
        }

        $kraRecords = [];
        $itemGroups = [];

        foreach ($rawRecords as $row) {
            $userName = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $fieldName = (string) ($row->field_name ?? '');
            $displayLabel = $fieldLabels[$fieldName] ?? ucwords(str_replace('_', ' ', $fieldName));
            $orderRank = $fieldOrder[$fieldName] ?? 99;
            $semItemId = (int) ($row->sem_item_id ?? 0);

            $rec = [
                'id' => $row->id,
                'sem_target_id' => $row->sem_target_id,
                'sem_item_id' => $semItemId,
                'field_name' => $fieldName,
                'field_label' => $displayLabel,
                'order_rank' => $orderRank,
                'original_value' => $row->original_value,
                'old_value' => $row->old_value,
                'new_value' => $row->new_value,
                'justification' => $row->justification ?: '-',
                'user_name' => $userName ?: 'System',
                'date_created' => $row->date_created ? \Illuminate\Support\Carbon::parse($row->date_created)->format('M d, Y h:i A') : '-',
            ];

            if ($fieldName === 'activity' || $semItemId === 0) {
                $kraRecords[] = $rec;
            } else {
                $itemGroups[$semItemId][] = $rec;
            }
        }

        $targetActivity = DB::table('ipc_sem_targets_indicator')
            ->where('id', $indicatorId)
            ->value('activity');

        if (empty($targetActivity)) {
            $actRecord = $rawRecords->firstWhere('field_name', 'activity');
            if ($actRecord) {
                $targetActivity = $actRecord->new_value ?: ($actRecord->old_value ?: ($actRecord->original_value ?: 'Key Result Area'));
            }
        }

        $kraTitle = trim((string) ($targetActivity ?: 'Key Result Area'));
        $kraTitleLimit = \Illuminate\Support\Str::limit($kraTitle, 75, '...');

        $allSections = [];

        // 1. Key Result Area on top
        if (!empty($kraRecords)) {
            $allSections[] = [
                'type' => 'kra',
                'title' => 'Key Result Area',
                'records' => $kraRecords,
            ];
        }

        // 2. Groups by sem_item_id using SUB TARGET #n of [Key Result Area]
        $subCounter = 1;
        foreach ($itemGroups as $semItemId => $gRecords) {
            $title = 'SUB TARGET #' . $subCounter . ' of ' . $kraTitleLimit;
            $allSections[] = [
                'type' => 'item',
                'title' => $title,
                'records' => $gRecords,
            ];
            $subCounter++;
        }

        $processedRecords = [];

        foreach ($allSections as $sIndex => $sec) {
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

        $this->historyRecords = $processedRecords;
        $this->showHistoryModal = true;
    }

    public function isHistoryTargetLocked(): bool
    {
        $targetId = $this->historyTargetId;
        if (!$targetId && $this->historyItemId) {
            $targetId = (int) DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $this->historyItemId)
                ->value('sem_target_id');
        }

        if (!$targetId) {
            return false;
        }

        $targetStatus = DB::table('ipc_sem_targets_indicator')
            ->where('id', $targetId)
            ->value('target_status');

        return (int) $targetStatus === 3;
    }

    public function discardEditHistory(): void
    {
        if ($this->isHistoryTargetLocked()) {
            Flux::toast(variant: 'danger', text: __('Cannot discard edit history because the target is locked.'));
            return;
        }

        $targetId = $this->historyTargetId;
        $itemId = $this->historyItemId;

        if (!$targetId && $itemId && $itemId > 0) {
            $targetId = (int) DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $itemId)
                ->value('sem_target_id');
        }

        if (!$targetId && !$itemId) {
            return;
        }

        $semId = $this->activeSemesterId();
        $userId = Auth::id();
        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($targetId, $itemId, $semId, $userId, $nowManila): void {
            $query = DB::table('ipc_sem_target_edit_histories');

            if ($itemId && $itemId > 0) {
                $query->where(function ($q) use ($itemId, $targetId) {
                    $q->where('sem_item_id', $itemId);
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
                            ->where('semester_id', $semId)
                            ->where('kra_category', $kraCategory)
                            ->max('display_order');

                        DB::table('ipc_sem_targets_indicator')->insert([
                            'id' => $targetId,
                            'ipc_target_indicator_id' => 0,
                            'semester_id' => $semId ?: 0,
                            'kra_category' => $kraCategory,
                            'display_order' => ((int) $maxOrder) + 1,
                            'activity' => $activity,
                            'target_status' => 1,
                            'created_by' => $userId ?: 1,
                            'date_created' => $nowManila,
                            'modified_by' => $userId ?: 1,
                            'last_date_modified' => $nowManila,
                            'target_from' => $userId ?: 1,
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
                                'sem_item_id' => $semId ?: 0,
                                'description' => $descRec ? ($descRec->old_value ?: ($descRec->original_value ?: 'Restored Sub-Target')) : 'Restored Sub-Target',
                                'rg_quantity' => $qtyRec ? ($qtyRec->old_value ?: $qtyRec->original_value) : null,
                                'rg_quality' => $qualRec ? ($qualRec->old_value ?: $qualRec->original_value) : null,
                                'rg_timeliness' => $timeRec ? ($timeRec->old_value ?: $timeRec->original_value) : null,
                                'rg_movs' => $movsRec ? ($movsRec->old_value ?: $movsRec->original_value) : null,
                                'rg_remarks' => $remRec ? ($remRec->old_value ?: $remRec->original_value) : null,
                                'remarks' => 1,
                                'created_by' => $userId ?: 1,
                                'date_created' => $nowManila,
                                'modified_by' => $userId ?: 1,
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
            if ($itemId && $itemId > 0) {
                $deleteQuery->where(function ($q) use ($itemId, $targetId) {
                    $q->where('sem_item_id', $itemId);
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

        $this->historyRecords = [];
        $this->showHistoryModal = false;
        $this->historyTargetId = null;
        $this->historyItemId = null;

        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Edit history discarded and values reverted to original successfully.'));
    }

    public function closeEditHistoryModal(): void
    {
        $this->showHistoryModal = false;
        $this->historyRecords = [];
        $this->historyTargetId = null;
        $this->historyItemId = null;
    }

    #[On('semestral-target-dropped')]
    public function targetDropped(array $source, array $target): void
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            $this->dispatch('semestral-target-swap-completed');
            return;
        }

        $sourceType = (string) ($source['type'] ?? '');
        $targetType = (string) ($target['type'] ?? '');
        $sourceIndicatorId = (int) ($source['indicatorId'] ?? 0);
        $targetIndicatorId = (int) ($target['indicatorId'] ?? 0);
        $sourceItemId = (int) ($source['itemId'] ?? 0);
        $targetItemId = (int) ($target['itemId'] ?? 0);

        if ($sourceType === 'main' && $sourceIndicatorId > 0 && $targetIndicatorId > 0 && $sourceIndicatorId !== $targetIndicatorId) {
            DB::transaction(function () use ($sourceIndicatorId, $targetIndicatorId): void {
                $source = DB::table('ipc_sem_targets_indicator')->where('id', $sourceIndicatorId)->lockForUpdate()->first();
                $target = DB::table('ipc_sem_targets_indicator')->where('id', $targetIndicatorId)->lockForUpdate()->first();

                if ($source === null || $target === null) {
                    return;
                }

                $sourceOrder = $source->display_order ?? $source->id;
                $targetOrder = $target->display_order ?? $target->id;

                DB::table('ipc_sem_targets_indicator')->where('id', $source->id)->update([
                    'kra_category' => $target->kra_category,
                    'display_order' => $targetOrder,
                ]);

                DB::table('ipc_sem_targets_indicator')->where('id', $target->id)->update([
                    'kra_category' => $source->kra_category,
                    'display_order' => $sourceOrder,
                ]);
            });

            $this->dispatch('semestral-target-swap-completed');
            Flux::toast(variant: 'success', text: __('Target position updated.'));
            return;
        }

        if ($sourceType === 'sub' && $sourceItemId > 0 && $targetItemId > 0 && $sourceItemId !== $targetItemId) {
            DB::transaction(function () use ($sourceItemId, $targetItemId): void {
                $source = DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $sourceItemId)->lockForUpdate()->first();
                $target = DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $targetItemId)->lockForUpdate()->first();

                if ($source === null || $target === null) {
                    return;
                }

                $sourceOrder = $source->display_order ?? $source->id;
                $targetOrder = $target->display_order ?? $target->id;

                DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $source->id)->update([
                    'sem_target_id' => $target->sem_target_id,
                    'display_order' => $targetOrder,
                ]);

                DB::table('ipc_sem_targets_indicator_itemlist')->where('id', $target->id)->update([
                    'sem_target_id' => $source->sem_target_id,
                    'display_order' => $sourceOrder,
                ]);
            });

            $this->dispatch('semestral-target-swap-completed');
            Flux::toast(variant: 'success', text: __('Sub-target position updated.'));
            return;
        }

        $this->dispatch('semestral-target-swap-completed');
    }

    public function semestralTargets(): LengthAwarePaginator
    {
        if ($this->semestralTargetsMemo !== null) {
            return $this->semestralTargetsMemo;
        }

        $userId = Auth::id();
        $showAll = $this->perPage === 'all';
        $effectivePerPage = $showAll ? 1 : (in_array((int) $this->perPage, [10, 25, 50], true) ? (int) $this->perPage : 10);

        if ($this->unauthorizedErrorMessage !== null) {
            return $this->semestralTargetsMemo = new LengthAwarePaginator([], 0, $effectivePerPage, 1);
        }

        $semesterNumber = $this->semId ? (string) ($this->getSemesterRecord()->semester ?? '') : '';
        $searchTerm = filled($this->search) ? '%' . trim($this->search) . '%' : null;

        $applyVisibleItems = function ($items) use ($semesterNumber): void {
            if ($semesterNumber === '1') {
                $items->where(function ($query): void {
                    $query->whereNull('new_semester')->orWhereIn('new_semester', [1, 3]);
                });
            } elseif ($semesterNumber === '2') {
                $items->where(function ($query): void {
                    $query->whereNull('new_semester')->orWhereIn('new_semester', [2, 3]);
                });
            }
        };

        $query = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->where('sem.user_id', $userId)
            ->when(!$this->includeStrategicFunction, fn($q) => $q->where('sti.kra_category', '!=', 1));

        if ($this->semId !== null && $this->semId > 0) {
            $query->where('sti.semester_id', $this->semId);
        }

        if (filled($this->categoryFilter)) {
            $query->where('sti.kra_category', $this->categoryFilter);
        }

        if ($this->targetStatusFilter === 'checkpoint') {
            $query->whereExists(function ($history): void {
                $history->selectRaw('1')
                    ->from('ipc_sem_target_edit_histories as history')
                    ->whereColumn('history.sem_target_id', 'sti.id');
            });
        }

        if ($this->targetStatusFilter === 'incomplete') {
            $query->whereExists(function ($items) use ($applyVisibleItems): void {
                $items->selectRaw('1')
                    ->from('ipc_sem_targets_indicator_itemlist as incomplete_items')
                    ->whereColumn('incomplete_items.sem_target_id', 'sti.id')
                    ->where(function ($incomplete): void {
                        $incomplete->where(function ($notAllNa): void {
                            $notAllNa->whereNull('incomplete_items.na_quantity')
                                ->orWhere('incomplete_items.na_quantity', '!=', 1)
                                ->orWhereNull('incomplete_items.na_quality')
                                ->orWhere('incomplete_items.na_quality', '!=', 1)
                                ->orWhereNull('incomplete_items.na_timeliness')
                                ->orWhere('incomplete_items.na_timeliness', '!=', 1);
                        })
                        ->where(function ($missingFields): void {
                            $missingFields->whereNull('incomplete_items.actual_accomp')
                                ->orWhereRaw("TRIM(COALESCE(incomplete_items.actual_accomp, '')) = ''")
                                ->orWhereNull('incomplete_items.has_attachments')
                                ->orWhere('incomplete_items.has_attachments', 0)
                                ->orWhereRaw("TRIM(COALESCE(incomplete_items.has_attachments, '')) = ''")
                                ->orWhereNull('incomplete_items.target_movs')
                                ->orWhereRaw("TRIM(COALESCE(incomplete_items.target_movs, '')) = ''")
                                ->orWhere(function ($qEmpty): void {
                                    $qEmpty->where(function ($qNull): void {
                                        $qNull->whereNull('incomplete_items.quantity_score')
                                            ->orWhereRaw("TRIM(COALESCE(incomplete_items.quantity_score, '')) IN ('', '0', '0.00')");
                                    })->where(function ($qNotNa): void {
                                        $qNotNa->whereNull('incomplete_items.na_quantity')
                                            ->orWhere('incomplete_items.na_quantity', '!=', 1);
                                    });
                                })
                                ->orWhere(function ($qlEmpty): void {
                                    $qlEmpty->where(function ($qlNull): void {
                                        $qlNull->whereNull('incomplete_items.quality_score')
                                            ->orWhereRaw("TRIM(COALESCE(incomplete_items.quality_score, '')) IN ('', '0', '0.00')");
                                    })->where(function ($qlNotNa): void {
                                        $qlNotNa->whereNull('incomplete_items.na_quality')
                                            ->orWhere('incomplete_items.na_quality', '!=', 1);
                                    });
                                })
                                ->orWhere(function ($tEmpty): void {
                                    $tEmpty->where(function ($tNull): void {
                                        $tNull->whereNull('incomplete_items.timeliness_score')
                                            ->orWhereRaw("TRIM(COALESCE(incomplete_items.timeliness_score, '')) IN ('', '0', '0.00')");
                                    })->where(function ($tNotNa): void {
                                        $tNotNa->whereNull('incomplete_items.na_timeliness')
                                            ->orWhere('incomplete_items.na_timeliness', '!=', 1);
                                    });
                                });
                        });
                    });
                $applyVisibleItems($items);
            });
        }

        $query->whereExists(function ($items) use ($applyVisibleItems): void {
            $items->selectRaw('1')
                ->from('ipc_sem_targets_indicator_itemlist')
                ->whereColumn('sem_target_id', 'sti.id');
            $applyVisibleItems($items);
        });

        if ($searchTerm !== null) {
            $query->where(function ($filter) use ($searchTerm, $applyVisibleItems): void {
                $filter->where('sti.activity', 'like', $searchTerm)
                    ->orWhereExists(function ($items) use ($searchTerm, $applyVisibleItems): void {
                        $items->selectRaw('1')
                            ->from('ipc_sem_targets_indicator_itemlist')
                            ->whereColumn('sem_target_id', 'sti.id')
                            ->where(function ($fields) use ($searchTerm): void {
                                $fields->where('description', 'like', $searchTerm)
                                    ->orWhere('rg_quantity', 'like', $searchTerm)
                                    ->orWhere('rg_quality', 'like', $searchTerm)
                                    ->orWhere('rg_timeliness', 'like', $searchTerm)
                                    ->orWhere('rg_movs', 'like', $searchTerm)
                                    ->orWhere('rg_remarks', 'like', $searchTerm);
                            });
                        $applyVisibleItems($items);
                    });
            });
        }

        $indicatorQuery = $query
            ->select('sti.id')
            ->orderBy('sem.year', 'desc')
            ->orderBy('sem.semester', 'asc')
            ->orderBy('sti.kra_category', 'asc')
            ->orderBy('sti.display_order', 'asc')
            ->orderBy('sti.id', 'asc');

        if ($showAll) {
            $indicatorIds = $indicatorQuery->pluck('sti.id')->map(fn ($id): int => (int) $id);
            $indicatorPage = new LengthAwarePaginator(
                collect(),
                $indicatorIds->count(),
                1,
                1,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'pageName' => 'page',
                ]
            );
            $indicatorPage->setCollection($indicatorIds->map(fn (int $id) => (object) ['id' => $id]));
        } else {
            $indicatorPage = $indicatorQuery->paginate($effectivePerPage);
            $indicatorIds = $indicatorPage->getCollection()->pluck('id')->map(fn ($id): int => (int) $id);
        }

        if ($indicatorIds->isEmpty()) {
            $indicatorPage->setCollection(collect());

            return $this->semestralTargetsMemo = $indicatorPage;
        }

        $rows = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'sti.id', '=', 'stil.sem_target_id')
            ->join('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->whereIn('sti.id', $indicatorIds)
            ->when($semesterNumber === '1', fn ($items) => $items->where(function ($query): void {
                $query->whereNull('stil.new_semester')->orWhereIn('stil.new_semester', [1, 3]);
            }))
            ->when($semesterNumber === '2', fn ($items) => $items->where(function ($query): void {
                $query->whereNull('stil.new_semester')->orWhereIn('stil.new_semester', [2, 3]);
            }))
            ->when($searchTerm !== null, function ($items) use ($searchTerm): void {
                $items->where(function ($fields) use ($searchTerm): void {
                    $fields->where('sti.activity', 'like', $searchTerm)
                        ->orWhere('stil.description', 'like', $searchTerm)
                        ->orWhere('stil.rg_quantity', 'like', $searchTerm)
                        ->orWhere('stil.rg_quality', 'like', $searchTerm)
                        ->orWhere('stil.rg_timeliness', 'like', $searchTerm)
                        ->orWhere('stil.rg_movs', 'like', $searchTerm)
                        ->orWhere('stil.rg_remarks', 'like', $searchTerm);
                });
            })
            ->select([
            'sti.id as sem_target_id',
            'sti.semester_id',
            'sti.kra_category',
            'sti.display_order as indicator_display_order',
            'sti.activity',
            'sti.target_status',
            'stil.id as sem_item_id',
            'stil.display_order as item_display_order',
            'stil.new_semester',
            'stil.description',
            'stil.rg_quantity',
            'stil.rg_quality',
            'stil.rg_timeliness',
            'stil.rg_ratingperiod',
            'stil.rg_movs',
            'stil.rg_remarks',
            'stil.remarks',
            'stil.quantity_score',
            'stil.quality_score',
            'stil.timeliness_score',
            'stil.na_quantity',
            'stil.na_quality',
            'stil.na_timeliness',
            'stil.average',
            'stil.actual_accomp',
            'stil.target_movs',
            'stil.target_remarks',
            'sem.year',
            'sem.semester',
            ])
            ->orderBy('sem.year', 'desc')
            ->orderBy('sem.semester', 'asc')
            ->orderBy('sti.kra_category', 'asc')
            ->orderBy('indicator_display_order', 'asc')
            ->orderBy('sem_target_id', 'asc')
            ->orderBy('item_display_order', 'asc')
            ->orderBy('sem_item_id', 'asc')
            ->get();

        $indicatorPage->setCollection($rows);

        return $this->semestralTargetsMemo = $indicatorPage;
    }

    protected function flushSemestralTargetCaches(): void
    {
        // No-op fallback for non-taggable cache stores.
    }

    public function openCopyModal(): void
    {
        if ($this->unauthorizedErrorMessage !== null) {
            Flux::toast(variant: 'danger', text: $this->unauthorizedErrorMessage);
            return;
        }

        $this->showCopyModal = true;

        if ($this->copySourceYear === '' || $this->copySourceSemester === '') {
            $activeSemId = $this->activeSemesterId();
            $semRecord = $activeSemId ? DB::table('ipc_semester')->where('id', $activeSemId)->first() : null;

            if ($semRecord && filled($semRecord->year) && filled($semRecord->semester)) {
                $currYear = (int) $semRecord->year;
                $currSem = (int) $semRecord->semester;
            } else {
                $currYear = (int) now()->year;
                $currSem = now()->month >= 7 ? 2 : 1;
            }

            if ($currSem === 2) {
                $prevYear = $currYear;
                $prevSem = 1;
            } else {
                $prevYear = $currYear - 1;
                $prevSem = 2;
            }

            if ($this->copySourceYear === '') {
                $this->copySourceYear = (string) $prevYear;
            }

            if ($this->copySourceSemester === '') {
                $this->copySourceSemester = (string) $prevSem;
            }
        }
    }

    public function closeCopyModal(): void
    {
        $this->showCopyModal = false;
    }

    /** @return Collection<int, object> */
    public function years(): Collection
    {
        $currentYear = now()->year;

        return collect(range(2021, $currentYear + 1))
            ->reverse()
            ->values()
            ->map(fn(int $year): object => (object) ['target_year' => (string) $year]);
    }

    /** @return Collection<int, object> */
    public function semesters(): Collection
    {
        return collect([
            (object) ['value' => '1', 'label' => __('1st Semester')],
            (object) ['value' => '2', 'label' => __('2nd Semester')],
            (object) ['value' => '3', 'label' => __('Both Semester')],
        ]);
    }

    public function copySourceYears(): Collection
    {
        $userId = Auth::id();
        if (!is_int($userId)) {
            return $this->years();
        }

        $years = DB::table('ipc_semester')
            ->where('user_id', $userId)
            ->whereNotNull('year')
            ->select('year as target_year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->get()
            ->map(fn(object $row): object => (object) ['target_year' => (string) $row->target_year]);

        return $years->isNotEmpty() ? $years : $this->years();
    }

    /** @return array<int, string> */
    public function getExistingActivitiesProperty(): array
    {
        $ipcSemesterId = $this->activeSemesterId();
        if (!$ipcSemesterId) {
            return [];
        }

        return DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $ipcSemesterId)
            ->pluck('activity')
            ->map(fn($act) => trim(mb_strtolower((string) $act)))
            ->filter()
            ->all();
    }

    public function updatedCopySourceYear(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceCategory(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceSemester(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceStatusFilter(): void
    {
        $this->copyPage = 1;
    }

    public function updatedCopySourceSearch(): void
    {
        $this->copyPage = 1;
    }

    public function previousCopyPage(): void
    {
        if ($this->copyPage > 1) {
            $this->copyPage--;
        }
    }

    public function nextCopyPage(): void
    {
        $this->copyPage++;
    }

    public function copySemestralTargetGroups(bool $paginate = true): LengthAwarePaginator|Collection
    {
        $userId = Auth::id();
        $activeSemId = $this->activeSemesterId();

        if (!$userId) {
            return $paginate ? new LengthAwarePaginator([], 0, max(1, $this->copyPerPage), 1) : collect();
        }

        $query = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'stil.sem_target_id', '=', 'sti.id')
            ->leftJoin('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->where('sem.user_id', $userId)
            ->where('sti.target_status', 3)
            ->where('stil.remarks', 3);

        if ($activeSemId) {
            $query->where('sti.semester_id', '!=', $activeSemId);
        }

        if ($this->copySourceYear !== '') {
            $query->where('sem.year', $this->copySourceYear);
        }

        if ($this->copySourceCategory !== '') {
            $query->where('sti.kra_category', (int) $this->copySourceCategory);
        }

        if ($this->copySourceSemester !== '') {
            $query->where(function ($q): void {
                $q->where('sem.semester', (int) $this->copySourceSemester)
                    ->orWhere('stil.new_semester', (int) $this->copySourceSemester);
            });
        }

        if (trim($this->copySourceSearch) !== '') {
            $search = '%' . trim($this->copySourceSearch) . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('sti.activity', 'like', $search)
                    ->orWhere('stil.description', 'like', $search);
            });
        }

        $rows = $query->select([
            'sti.id as ind_id',
            'sti.kra_category',
            'sti.activity',
            'sem.year as target_year',
            'sem.semester as target_sem',
            'stil.id as item_id',
            'stil.new_semester',
            'stil.description',
            'stil.rg_quantity',
            'stil.rg_quality',
            'stil.rg_timeliness',
            'stil.rg_ratingperiod',
            'stil.rg_movs',
            'stil.rg_remarks',
        ])
            ->orderBy('sem.year', 'desc')
            ->orderBy('sem.semester', 'asc')
            ->orderBy('sti.kra_category', 'asc')
            ->orderBy('sti.display_order', 'asc')
            ->orderBy('stil.display_order', 'asc')
            ->get();

        $grouped = $rows->groupBy('ind_id');

        if (!$paginate) {
            return $grouped;
        }

        $total = $grouped->count();
        $perPage = max(1, $this->copyPerPage);
        $lastPage = (int) ceil($total / $perPage) ?: 1;
        $currentPage = max(1, min($this->copyPage, $lastPage));
        if ($this->copyPage !== $currentPage) {
            $this->copyPage = $currentPage;
        }

        $pagedItems = $grouped->slice(($currentPage - 1) * $perPage, $perPage);

        return new LengthAwarePaginator(
            $pagedItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'copyPage',
            ]
        );
    }

    public function isSemestralTargetLocked(): bool
    {
        $semRecord = $this->getSemesterRecord();
        if (!$semRecord) {
            return false;
        }

        return in_array((int) ($semRecord->lock ?? 0), [1, 2], true);
    }

    protected function canModifyActiveSemester(?int $semTargetId = null, ?int $semItemId = null): bool
    {
        $query = DB::table('ipc_semester as semester')
            ->where('semester.id', $this->semId)
            ->where('semester.user_id', Auth::id())
            ->where('semester.lock', 0);

        if ($semTargetId !== null) {
            $query->whereExists(function ($target) use ($semTargetId): void {
                $target->selectRaw('1')
                    ->from('ipc_sem_targets_indicator as editable_target')
                    ->whereColumn('editable_target.semester_id', 'semester.id')
                    ->where('editable_target.id', $semTargetId);
            });
        }

        if ($semItemId !== null) {
            $query->whereExists(function ($item) use ($semItemId): void {
                $item->selectRaw('1')
                    ->from('ipc_sem_targets_indicator_itemlist as editable_item')
                    ->join('ipc_sem_targets_indicator as item_target', 'item_target.id', '=', 'editable_item.sem_target_id')
                    ->whereColumn('item_target.semester_id', 'semester.id')
                    ->where('editable_item.id', $semItemId);
            });
        }

        if ($query->exists()) {
            return true;
        }

        $this->cachedSemRecord = null;
        Flux::toast(variant: 'danger', text: __('This semester is locked or unavailable. Target changes are no longer allowed.'));

        return false;
    }

    public function hasIncompleteSemestralTargets(): bool
    {
        $semId = $this->semId;
        if (!$semId) {
            return true;
        }

        $totalIndicators = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $semId)
            ->count();

        if ($totalIndicators === 0) {
            return true;
        }

        return DB::table('ipc_sem_targets_indicator as sti')
            ->where('sti.semester_id', $semId)
            ->where(function ($q): void {
                $q->whereNotExists(function ($sub): void {
                    $sub->selectRaw('1')
                        ->from('ipc_sem_targets_indicator_itemlist as item')
                        ->whereColumn('item.sem_target_id', 'sti.id');
                })
                ->orWhereExists(function ($items): void {
                    $items->selectRaw('1')
                        ->from('ipc_sem_targets_indicator_itemlist as incomplete_items')
                        ->whereColumn('incomplete_items.sem_target_id', 'sti.id')
                        ->where(function ($incomplete): void {
                            $incomplete->where(function ($notAllNa): void {
                                $notAllNa->whereNull('incomplete_items.na_quantity')
                                    ->orWhere('incomplete_items.na_quantity', '!=', 1)
                                    ->orWhereNull('incomplete_items.na_quality')
                                    ->orWhere('incomplete_items.na_quality', '!=', 1)
                                    ->orWhereNull('incomplete_items.na_timeliness')
                                    ->orWhere('incomplete_items.na_timeliness', '!=', 1);
                            })
                            ->where(function ($missingFields): void {
                                $missingFields->whereNull('incomplete_items.actual_accomp')
                                    ->orWhereRaw("TRIM(COALESCE(incomplete_items.actual_accomp, '')) = ''")
                                    ->orWhereNull('incomplete_items.has_attachments')
                                    ->orWhere('incomplete_items.has_attachments', 0)
                                    ->orWhereRaw("TRIM(COALESCE(incomplete_items.has_attachments, '')) = ''")
                                    ->orWhereNull('incomplete_items.target_movs')
                                    ->orWhereRaw("TRIM(COALESCE(incomplete_items.target_movs, '')) = ''")
                                    ->orWhere(function ($qEmpty): void {
                                        $qEmpty->where(function ($qNull): void {
                                            $qNull->whereNull('incomplete_items.quantity_score')
                                                ->orWhereRaw("TRIM(COALESCE(incomplete_items.quantity_score, '')) IN ('', '0', '0.00')");
                                        })->where(function ($qNotNa): void {
                                            $qNotNa->whereNull('incomplete_items.na_quantity')
                                                ->orWhere('incomplete_items.na_quantity', '!=', 1);
                                        });
                                    })
                                    ->orWhere(function ($qlEmpty): void {
                                        $qlEmpty->where(function ($qlNull): void {
                                            $qlNull->whereNull('incomplete_items.quality_score')
                                                ->orWhereRaw("TRIM(COALESCE(incomplete_items.quality_score, '')) IN ('', '0', '0.00')");
                                        })->where(function ($qlNotNa): void {
                                            $qlNotNa->whereNull('incomplete_items.na_quality')
                                                ->orWhere('incomplete_items.na_quality', '!=', 1);
                                        });
                                    })
                                    ->orWhere(function ($tEmpty): void {
                                        $tEmpty->where(function ($tNull): void {
                                            $tNull->whereNull('incomplete_items.timeliness_score')
                                                ->orWhereRaw("TRIM(COALESCE(incomplete_items.timeliness_score, '')) IN ('', '0', '0.00')");
                                        })->where(function ($tNotNa): void {
                                            $tNotNa->whereNull('incomplete_items.na_timeliness')
                                                ->orWhere('incomplete_items.na_timeliness', '!=', 1);
                                        });
                                    });
                            });
                        });
                });
            })
            ->exists();
    }


    public function openLockConfirmModal(): void
    {
        $this->showLockConfirmModal = true;
    }

    public function cancelLockConfirm(): void
    {
        $this->showLockConfirmModal = false;
    }

    public function saveAndLockSemestralTarget(): void
    {
        $semId = $this->activeSemesterId();
        if (!$semId) {
            Flux::toast(variant: 'danger', text: __('No semestral target record selected.'));
            $this->cancelLockConfirm();
            return;
        }

        $semTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $semId)
            ->pluck('id')
            ->all();

        if (empty($semTargetIds)) {
            Flux::toast(variant: 'warning', text: __('No semestral targets found to lock.'));
            $this->cancelLockConfirm();
            return;
        }

        DB::transaction(function () use ($semId, $semTargetIds): void {
            DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $semId)
                ->where('target_status', 1)
                ->update(['target_status' => 3]);

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->whereIn('sem_target_id', $semTargetIds)
                ->where('remarks', 1)
                ->update([
                    'remarks' => 3,
                    'target_movs' => DB::raw('rg_movs'),
                    'target_remarks' => DB::raw('rg_remarks'),
                ]);

            DB::table('ipc_semester')
                ->where('id', $semId)
                ->update(['lock' => 1]);
        });

        $this->cancelLockConfirm();
        $this->dispatch('semestral-target-updated');
        $this->dispatch('semestral-target-reload');
        Flux::toast(variant: 'success', text: __('Semestral target saved and locked successfully.'));
    }

    public function openUnlockConfirmModal(): void
    {
        $this->showUnlockConfirmModal = true;
    }

    public function cancelUnlockConfirm(): void
    {
        $this->showUnlockConfirmModal = false;
    }

    public function saveAndUnlockSemestralTarget(): void
    {
        $semId = $this->activeSemesterId();
        if (!$semId) {
            Flux::toast(variant: 'danger', text: __('No semestral target record selected.'));
            $this->cancelUnlockConfirm();
            return;
        }

        $semTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $semId)
            ->pluck('id')
            ->all();

        if (empty($semTargetIds)) {
            Flux::toast(variant: 'warning', text: __('No semestral targets found to unlock.'));
            $this->cancelUnlockConfirm();
            return;
        }

        DB::transaction(function () use ($semId, $semTargetIds): void {
            DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $semId)
                ->where('target_status', 3)
                ->update(['target_status' => 1]);

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->whereIn('sem_target_id', $semTargetIds)
                ->where('remarks', 3)
                ->update(['remarks' => 1]);

            DB::table('ipc_semester')
                ->where('id', $semId)
                ->update(['lock' => 0]);
        });

        $this->cancelUnlockConfirm();
        $this->dispatch('semestral-target-updated');
        $this->dispatch('semestral-target-reload');
        Flux::toast(variant: 'success', text: __('Semestral target unlocked successfully.'));
    }

    public function requestCopyAllSemestral(): void
    {
        $this->showCopyAllConfirmModal = true;
    }

    public function cancelCopyAllConfirm(): void
    {
        $this->showCopyAllConfirmModal = false;
    }

    public function confirmCopyAll(): void
    {
        $this->showCopyAllConfirmModal = false;
        $this->copyAllSemestralTargetGroups();
    }

    public function copyAllSemestralTargetGroups(): void
    {
        $groups = $this->copySemestralTargetGroups(false);
        if ($groups->isEmpty()) {
            return;
        }

        $existingActivities = $this->existingActivities;
        $copiedCount = 0;

        foreach ($groups as $indicatorId => $items) {
            $first = $items->first();
            if ($first === null) {
                continue;
            }

            $activityClean = trim(mb_strtolower((string) ($first->activity ?? '')));
            if ($activityClean !== '' && in_array($activityClean, $existingActivities, true)) {
                continue;
            }

            $this->copySemestralTargetGroup((int) $indicatorId);
            $existingActivities[] = $activityClean;
            $copiedCount++;
        }

        if ($copiedCount > 0) {
            Flux::toast(variant: 'success', text: __(':count semestral target group(s) copied successfully.', ['count' => $copiedCount]));
        } else {
            Flux::toast(variant: 'warning', text: __('No new semestral targets available to copy (all matching results already exist).'));
        }
    }

    public function copySemestralTargetGroup(int $indicatorId): void
    {
        $ipcSemesterId = $this->activeSemesterId();
        $userId = Auth::id();

        if (!$ipcSemesterId || !$userId) {
            Flux::toast(variant: 'danger', text: __('Unable to copy target. Semester not found.'));
            return;
        }

        $sourceIndicator = DB::table('ipc_sem_targets_indicator')
            ->where('id', $indicatorId)
            ->where('target_status', 3)
            ->first();
        if ($sourceIndicator === null) {
            return;
        }

        $sourceItems = DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('sem_target_id', $indicatorId)
            ->where('remarks', 3)
            ->get();

        $nowManila = \Illuminate\Support\Carbon::now('Asia/Manila');

        DB::transaction(function () use ($sourceIndicator, $sourceItems, $ipcSemesterId, $userId, $nowManila): void {
            $maxOrder = (int) DB::table('ipc_sem_targets_indicator')
                ->where('semester_id', $ipcSemesterId)
                ->where('kra_category', $sourceIndicator->kra_category)
                ->max('display_order');

            $newSemTargetId = (int) DB::table('ipc_sem_targets_indicator')->insertGetId([
                'ipc_target_indicator_id' => $sourceIndicator->ipc_target_indicator_id ?? 0,
                'semester_id' => $ipcSemesterId,
                'kra_category' => $sourceIndicator->kra_category,
                'display_order' => $maxOrder + 1,
                'activity' => $sourceIndicator->activity,
                'verified' => null,
                'verified_by' => null,
                'date_verified' => null,
                'remarks' => $sourceIndicator->remarks,
                'target_status' => 1,
                'created_by' => $userId,
                'date_created' => $nowManila,
                'modified_by' => $userId,
                'last_date_modified' => $nowManila,
                'target_from' => $userId,
            ]);

            foreach ($sourceItems as $item) {
                $newItemId = (int) DB::table('ipc_sem_targets_indicator_itemlist')->insertGetId([
                    'target_orig_id' => $item->target_orig_id ?? 0,
                    'sem_target_id' => $newSemTargetId,
                    'display_order' => $item->display_order,
                    'sem_item_id' => $ipcSemesterId,
                    'new_semester' => $item->new_semester,
                    'description' => $item->description,
                    'actual_accomp' => null,
                    'weight' => $item->weight,
                    'quantity' => $item->quantity,
                    'quality' => $item->quality,
                    'timeliness' => $item->timeliness,
                    'rg_quantity' => $item->rg_quantity,
                    'rg_quality' => $item->rg_quality,
                    'rg_timeliness' => $item->rg_timeliness,
                    'rg_ratingperiod' => $item->rg_ratingperiod,
                    'rg_movs' => $item->rg_movs,
                    'rg_remarks' => $item->rg_remarks,
                    'remarks' => 1,
                    'created_by' => $userId,
                    'date_created' => $nowManila,
                    'modified_by' => $userId,
                    'date_modified' => $nowManila,
                ]);

                $this->logSemTargetHistory($newSemTargetId, $newItemId, 'created', null, (string) ($item->description ?? ''), $userId, $nowManila, 'Target Copied');
            }
        });

        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Target copied successfully.'));
    }

    protected function logSemTargetHistory(
        int $semTargetId,
        ?int $semItemId,
        string $fieldName,
        ?string $oldVal,
        ?string $newVal,
        int $userId,
        \Illuminate\Support\Carbon $now,
        ?string $justification = null
    ): void {
        DB::table('ipc_sem_target_edit_histories')->insert([
            'sem_target_id' => $semTargetId,
            'sem_item_id' => $semItemId,
            'field_name' => $fieldName,
            'original_value' => $oldVal,
            'old_value' => $oldVal,
            'new_value' => $newVal,
            'last_edited_value' => $oldVal,
            'justification' => $justification,
            'user_id' => $userId,
            'date_created' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    #[Renderless]
    public function batchSaveScores(array $items): void
    {
        if (empty($items)) {
            return;
        }

        $items = array_slice($items, 0, 100);
        $nowManila = Carbon::now('Asia/Manila');
        $userId = Auth::id();

        abort_if($userId === null, 403);

        $ids = array_filter(array_map(fn($i) => (int) ($i['id'] ?? 0), $items));
        if (empty($ids)) {
            return;
        }

        $existingRecords = DB::table('ipc_sem_targets_indicator_itemlist as item')
            ->join('ipc_sem_targets_indicator as target', 'target.id', '=', 'item.sem_target_id')
            ->join('ipc_semester as semester', 'semester.id', '=', 'target.semester_id')
            ->where('semester.user_id', $userId)
            ->whereIn('semester.lock', [0, 1])
            ->whereIn('item.id', $ids)
            ->select('item.*')
            ->get()
            ->keyBy('id');

        $floatEqual = function ($a, $b) {
            if ($a === null && $b === null) return true;
            if ($a === null || $b === null) return false;
            return abs((float) $a - (float) $b) < 0.0001;
        };

        $intEqual = function ($a, $b) {
            if ($a === null && $b === null) return true;
            if ($a === null || $b === null) return false;
            return (int) $a === (int) $b;
        };

        $stringEqual = function ($a, $b) {
            $strA = $a !== null ? trim((string) $a) : '';
            $strB = $b !== null ? trim((string) $b) : '';
            return $strA === $strB;
        };

        $updatedCount = 0;
        $savedItems = [];

        DB::transaction(function () use ($items, $existingRecords, $floatEqual, $intEqual, $stringEqual, $nowManila, $userId, &$updatedCount, &$savedItems) {
            foreach ($items as $item) {
                $itemId = (int) ($item['id'] ?? 0);
                $existing = $existingRecords->get($itemId);
                if (! $existing) {
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

                $dbAverage = is_numeric($avgRaw) ? round((float) $avgRaw, 5) : null;
                $actualAccomp = isset($item['actual_accomp']) ? (string) $item['actual_accomp'] : null;
                $targetMovs = isset($item['target_movs']) ? (string) $item['target_movs'] : null;
                $targetRemarks = isset($item['target_remarks']) ? (string) $item['target_remarks'] : null;

                $hasChanged = false;
                if (! $floatEqual($existing->quantity_score, $dbQ)) $hasChanged = true;
                if (! $floatEqual($existing->quality_score, $dbQl)) $hasChanged = true;
                if (! $floatEqual($existing->timeliness_score, $dbT)) $hasChanged = true;
                if (! $intEqual($existing->na_quantity, $naQ)) $hasChanged = true;
                if (! $intEqual($existing->na_quality, $naQl)) $hasChanged = true;
                if (! $intEqual($existing->na_timeliness, $naT)) $hasChanged = true;
                if (! $floatEqual($existing->average, $dbAverage)) $hasChanged = true;
                if (! $stringEqual($existing->actual_accomp, $actualAccomp)) $hasChanged = true;
                if (! $stringEqual($existing->target_movs, $targetMovs)) $hasChanged = true;
                if (! $stringEqual($existing->target_remarks, $targetRemarks)) $hasChanged = true;

                if ($hasChanged) {
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

                    $updatedCount++;
                }

                $savedItems[$itemId] = [
                    'quantity_score' => $isQNa ? 'N/A' : ($dbQ !== null ? (string) $dbQ : ''),
                    'quality_score' => $isQlNa ? 'N/A' : ($dbQl !== null ? (string) $dbQl : ''),
                    'timeliness_score' => $isTNa ? 'N/A' : ($dbT !== null ? (string) $dbT : ''),
                    'average' => $dbAverage !== null ? number_format($dbAverage, 5, '.', '') : (($isQNa || $isQlNa || $isTNa) && $dbAverage === null ? 'N/A' : ''),
                    'actual_accomp' => $actualAccomp ?? '',
                    'target_movs' => $targetMovs ?? '',
                    'target_remarks' => $targetRemarks ?? '',
                ];
            }
        });

        $this->calculateFinalRating();
        $this->dispatch('semestral-target-scores-saved', savedItems: $savedItems);

        try {
            if ($updatedCount > 0) {
                Flux::toast(
                    variant: 'success',
                    heading: __('Saved Successfully'),
                    text: __(':count target item(s) updated successfully.', ['count' => $updatedCount]),
                    position: 'top right'
                );
            }
        } catch (\Throwable $e) {
            // Ignore toast outside Livewire HTTP lifecycle
        }
    }

    public function computeBackendFunctionScores(): array
    {
        if (! $this->semId) {
            return [
                'coreScore' => '0.00000',
                'supportScore' => '0.00000',
                'strategicScore' => '0.00000',
                'finalScore' => '0.00000',
                'adjectival' => 'N/A',
            ];
        }

        $items = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stii', 'stii.sem_target_id', '=', 'sti.id')
            ->where('sti.semester_id', $this->semId)
            ->select([
                'sti.kra_category',
                'stii.quantity_score',
                'stii.quality_score',
                'stii.timeliness_score',
                'stii.na_quantity',
                'stii.na_quality',
                'stii.na_timeliness',
                'stii.average',
            ])
            ->get();

        $categoryAverages = [
            1 => [], // Strategic
            2 => [], // Core
            3 => [], // Support
        ];

        foreach ($items as $item) {
            $cat = (int) ($item->kra_category ?? 2);

            $rowAvg = null;
            if ($item->average !== null && trim((string) $item->average) !== '' && trim((string) $item->average) !== '-') {
                $val = (float) $item->average;
                if ($val > 0) {
                    $rowAvg = $val;
                }
            }

            if ($rowAvg === null) {
                $scores = [];
                if ((int) ($item->na_quantity ?? 0) !== 1 && is_numeric($item->quantity_score) && (float) $item->quantity_score > 0) {
                    $scores[] = (float) $item->quantity_score;
                }
                if ((int) ($item->na_quality ?? 0) !== 1 && is_numeric($item->quality_score) && (float) $item->quality_score > 0) {
                    $scores[] = (float) $item->quality_score;
                }
                if ((int) ($item->na_timeliness ?? 0) !== 1 && is_numeric($item->timeliness_score) && (float) $item->timeliness_score > 0) {
                    $scores[] = (float) $item->timeliness_score;
                }
                if (count($scores) > 0) {
                    $rowAvg = array_sum($scores) / count($scores);
                }
            }

            if ($rowAvg !== null && $rowAvg > 0) {
                $categoryAverages[$cat][] = $rowAvg;
            }
        }

        $calcAvg = function (array $values): float {
            return count($values) > 0 ? (array_sum($values) / count($values)) : 0.0;
        };

        $strategicVal = $this->includeStrategicFunction ? $calcAvg($categoryAverages[1] ?? []) : 0.0;
        $coreVal = $calcAvg($categoryAverages[2] ?? []);
        $supportVal = $calcAvg($categoryAverages[3] ?? []);

        $format5Decimals = function (float $num): string {
            if ($num <= 0) return '0.00000';
            $str = (string) $num;
            $parts = explode('.', $str);
            $intPart = $parts[0];
            $decPart = $parts[1] ?? '';
            if (strlen($decPart) < 5) {
                $decPart = str_pad($decPart, 5, '0');
            } else {
                $decPart = substr($decPart, 0, 5);
            }
            return "{$intPart}.{$decPart}";
        };

        $coreScore = $format5Decimals($coreVal);
        $supportScore = $format5Decimals($supportVal);
        $strategicScore = $format5Decimals($strategicVal);

        $rawFinal = 0.0;
        if ($this->includeStrategicFunction) {
            $validCategories = 0;
            $totalSum = 0.0;
            if ($strategicVal > 0) { $totalSum += $strategicVal; $validCategories++; }
            if ($coreVal > 0) { $totalSum += $coreVal; $validCategories++; }
            if ($supportVal > 0) { $totalSum += $supportVal; $validCategories++; }
            $rawFinal = $validCategories > 0 ? ($totalSum / $validCategories) : 0.0;
        } else {
            $validCategories = 0;
            $totalSum = 0.0;
            if ($coreVal > 0) { $totalSum += $coreVal; $validCategories++; }
            if ($supportVal > 0) { $totalSum += $supportVal; $validCategories++; }
            $rawFinal = $validCategories > 0 ? ($totalSum / $validCategories) : 0.0;
        }

        $finalScore = $format5Decimals($rawFinal);
        $calcVal = (float) $finalScore;

        if ($calcVal >= 5.00) $adjectival = __('Outstanding');
        elseif ($calcVal >= 4.00) $adjectival = __('Very Satisfactory');
        elseif ($calcVal >= 3.00) $adjectival = __('Satisfactory');
        elseif ($calcVal >= 2.00) $adjectival = __('Unsatisfactory');
        elseif ($calcVal > 0) $adjectival = __('Poor');
        else $adjectival = 'N/A';

        return [
            'coreScore' => $coreScore,
            'supportScore' => $supportScore,
            'strategicScore' => $strategicScore,
            'finalScore' => $finalScore,
            'adjectival' => $adjectival,
        ];
    }

    public function render(): View
    {
        $categories = $this->categories();
        $visibleCategories = $this->categoryFilter === ''
            ? $categories
            : $categories->where('value', $this->categoryFilter)->values();

        $scores = $this->computeBackendFunctionScores();
        $this->finalRating = $scores['finalScore'];
        $this->adjectivalRating = $scores['adjectival'];

        return view('livewire.pages.semestral-target-page', [
            'semestralTargets' => $this->semestralTargets(),
            'categories' => $categories,
            'visibleCategories' => $visibleCategories,
            'semesterHeading' => $this->semesterHeading(),
            'functionScores' => $scores,
        ]);
    }
}
