<?php

namespace App\Livewire\Pages;

use App\Models\ApplicationSetting;
use App\Support\KraCategory;
use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Semestral Target')]
class SemestralTargetPage extends Component
{
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
    public bool $hasCheckpointTarget = false;
    public int $perPage = 10;
    public bool $includeStrategicFunction = true;

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

    // Lock/Unlock Confirm Modals
    public bool $showLockConfirmModal = false;
    public bool $showUnlockConfirmModal = false;

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
        $this->hasCheckpointTarget = (bool) Session::get($this->sessionKey('hasCheckpointTarget'), false);
        $this->search = (string) Session::get($this->sessionKey('search'), '');
        $this->perPage = (int) Session::get($this->sessionKey('perPage'), 10);

        if (!$this->includeStrategicFunction && $this->categoryFilter === '1') {
            $this->categoryFilter = '';
        }

        $this->validateSemId();
        $this->loadUserProfile();
        $this->calculateFinalRating(silent: true);

        if ($this->unauthorizedErrorMessage !== null) {
            Flux::toast(variant: 'danger', text: $this->unauthorizedErrorMessage);
        }
    }

    public function loadSemesterRatings(): void
    {
        if (!$this->semId) {
            $this->finalRating = '';
            $this->adjectivalRating = '';
            return;
        }

        $sem = DB::table('ipc_semester')->where('id', $this->semId)->first();
        if ($sem) {
            $this->finalRating = filled($sem->final_rating) ? number_format((float) $sem->final_rating, 2, '.', '') : '0.00';
            $this->adjectivalRating = filled($sem->adjectival_rating) ? (string) $sem->adjectival_rating : 'N/A';
        }
    }

    #[On('semestral-target-updated')]
    public function calculateFinalRating(bool $silent = true): void
    {
        if (!$this->semId) {
            return;
        }

        $avgScore = DB::table('ipc_sem_targets_indicator_itemlist as stil')
            ->join('ipc_sem_targets_indicator as sti', 'stil.sem_target_id', '=', 'sti.id')
            ->where('sti.semester_id', $this->semId)
            ->whereNotNull('stil.average')
            ->where('stil.average', '!=', '')
            ->where('stil.average', '>', 0)
            ->avg('stil.average');

        if ($avgScore !== null) {
            $calcFinal = round((float) $avgScore, 2);
            $finalStr = number_format($calcFinal, 2, '.', '');
        } else {
            $calcFinal = 0.0;
            $finalStr = '0.00';
        }

        $adjectival = match (true) {
            $calcFinal >= 4.50 => 'Outstanding',
            $calcFinal >= 3.50 => 'Very Satisfactory',
            $calcFinal >= 2.50 => 'Satisfactory',
            $calcFinal >= 1.50 => 'Unsatisfactory',
            $calcFinal > 0.00 => 'Poor',
            default => 'N/A',
        };

        DB::table('ipc_semester')
            ->where('id', $this->semId)
            ->update([
                'final_rating' => $finalStr,
                'adjectival_rating' => $adjectival,
            ]);

        $this->finalRating = $finalStr;
        $this->adjectivalRating = $adjectival;

        if (!$silent) {
            Flux::toast(
                variant: 'success',
                text: __('Final Rating calculated: :rating (:adjectival)', ['rating' => $finalStr, 'adjectival' => $adjectival])
            );
        }
    }

    public function imReady(): void
    {
        if (!$this->semId) {
            return;
        }

        Flux::toast(variant: 'success', text: __("You have indicated that you are ready!"));
    }

    protected function validateSemId(): void
    {
        $userId = Auth::id();

        if ($this->semId !== null && $this->semId > 0 && $userId !== null) {
            $semRecord = DB::table('ipc_semester')->where('id', $this->semId)->first();

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

    public function updatedHasCheckpointTarget(): void
    {
        $this->resetPage();
        Session::put($this->sessionKey('hasCheckpointTarget'), $this->hasCheckpointTarget);
    }

    public function updatedPerPage(): void
    {
        if (!in_array((int) $this->perPage, [10, 25, 50, 100, -1], true)) {
            $this->perPage = 10;
        }
        $this->resetPage();
        Session::put($this->sessionKey('perPage'), $this->perPage);
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryFilter = '';
        $this->hasCheckpointTarget = false;
        $this->perPage = 10;

        Session::forget([
            $this->sessionKey('search'),
            $this->sessionKey('categoryFilter'),
            $this->sessionKey('hasCheckpointTarget'),
            $this->sessionKey('perPage'),
        ]);

        $this->resetPage();
    }

    public function printIpcrf(): void
    {
        Flux::toast(variant: 'info', text: __('Preparing IPCR-F document for print...'));
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
            (object) ['value' => 100, 'label' => '100'],
            (object) ['value' => -1, 'label' => 'All'],
        ];
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
        $this->resetValidation();

        // Toast notification first if target has existing history and cannot be deleted
        $hasHistory = DB::table('ipc_sem_target_edit_histories')
            ->where('sem_target_id', $semTargetId)
            ->exists();

        if ($hasHistory) {
            Flux::toast(variant: 'danger', text: __('Cannot delete target because it has existing edit history.'));
            return;
        }

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

        $hasHistory = DB::table('ipc_sem_target_edit_histories')->where('sem_target_id', $semTargetId)->exists();

        // Rule: Prevent delete if target has existing edit history
        if ($hasHistory) {
            $this->cancelDeleteTarget();
            Flux::toast(variant: 'danger', text: __('Cannot delete target because it has existing edit history.'));
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
        $semId = $this->activeSemesterId();
        if (!$semId) {
            $this->deletedTargetsList = [];
            $this->showRecoverModal = true;
            return;
        }

        $activeTargetIds = DB::table('ipc_sem_targets_indicator')
            ->where('semester_id', $semId)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $activeItemIds = DB::table('ipc_sem_targets_indicator_itemlist')
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();

        $deletedHistoryRecords = DB::table('ipc_sem_target_edit_histories as h')
            ->leftJoin('users as u', 'h.user_id', '=', 'u.id')
            ->where('h.field_name', 'deleted')
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
            ->orderBy('h.id', 'desc')
            ->get();

        $deletedList = [];

        foreach ($deletedHistoryRecords as $delEvent) {
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

            $userName = '-';
            if ($delEvent->user_id) {
                $userObj = DB::table('users')->where('id', $delEvent->user_id)->select('first_name', 'last_name')->first();
                if ($userObj) {
                    $userName = trim(($userObj->first_name ?? '') . ' ' . ($userObj->last_name ?? ''));
                }
            }

            $dateFormatted = $delEvent->date_created ? \Illuminate\Support\Carbon::parse($delEvent->date_created)->format('M d, Y h:i A') : '-';

            $deletedList[] = [
                'sem_target_id' => (int) $targetId,
                'kra_category' => $kraCategory,
                'kra_category_label' => KraCategory::label($kraCategory),
                'activity' => $activity,
                'description' => $description,
                'justification' => $delEvent->justification ?: '-',
                'deleted_at' => $dateFormatted,
                'user_name' => $userName ?: 'System',
            ];
        }

        $this->deletedTargetsList = collect($deletedList)->unique('sem_target_id')->values()->all();
        $this->showRecoverModal = true;
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
        $hasHistory = DB::table('ipc_sem_target_edit_histories')
            ->where('sem_item_id', $semItemId)
            ->exists();

        if ($hasHistory) {
            Flux::toast(variant: 'danger', text: __('Cannot delete sub-target because it has existing edit history.'));
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

            Flux::toast(variant: 'success', text: __('Sub-target position updated.'));
        }
    }

    public function semestralTargets(): LengthAwarePaginator
    {
        $userId = Auth::id();

        if ($this->unauthorizedErrorMessage !== null) {
            return new LengthAwarePaginator([], 0, (int) $this->perPage <= 0 ? 10 : (int) $this->perPage, 1);
        }

        $query = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stil', 'sti.id', '=', 'stil.sem_target_id')
            ->leftJoin('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->where('sem.user_id', $userId)
            ->when(!$this->includeStrategicFunction, fn($q) => $q->where('sti.kra_category', '!=', 1));

        if ($this->semId !== null && $this->semId > 0) {
            $query->where('sti.semester_id', $this->semId);

            $semNumber = DB::table('ipc_semester')
                ->where('id', $this->semId)
                ->value('semester');

            if ((string) $semNumber === '1') {
                $query->where(function ($q): void {
                    $q->whereNull('stil.new_semester')
                        ->orWhereIn('stil.new_semester', [1, 3]);
                });
            } elseif ((string) $semNumber === '2') {
                $query->where(function ($q): void {
                    $q->whereNull('stil.new_semester')
                        ->orWhereIn('stil.new_semester', [2, 3]);
                });
            }
        }

        if (filled($this->categoryFilter)) {
            $query->where('sti.kra_category', $this->categoryFilter);
        }

        if ($this->hasCheckpointTarget) {
            $checkpointTargetIds = DB::table('ipc_sem_target_edit_histories')
                ->pluck('sem_target_id')
                ->unique()
                ->all();

            $query->whereIn('sti.id', $checkpointTargetIds);
        }

        if (filled($this->search)) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm): void {
                $q->where('sti.activity', 'like', $searchTerm)
                    ->orWhere('stil.description', 'like', $searchTerm)
                    ->orWhere('stil.rg_quantity', 'like', $searchTerm)
                    ->orWhere('stil.rg_quality', 'like', $searchTerm)
                    ->orWhere('stil.rg_timeliness', 'like', $searchTerm)
                    ->orWhere('stil.rg_movs', 'like', $searchTerm)
                    ->orWhere('stil.rg_remarks', 'like', $searchTerm);
            });
        }

        $query->select([
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
            ->orderBy('sti.display_order', 'asc')
            ->orderBy('sti.id', 'asc')
            ->orderBy('stil.display_order', 'asc')
            ->orderBy('stil.id', 'asc');

        $effectivePerPage = (int) $this->perPage <= 0 ? max(1, (clone $query)->count()) : (int) $this->perPage;

        return $query->paginate($effectivePerPage);
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
        $semId = $this->semId ?: request()->query('sem_id');
        if (!$semId) {
            return false;
        }

        $semLock = DB::table('ipc_semester')
            ->where('id', $semId)
            ->value('lock');

        return (int) $semLock === 1;
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

    public function batchSaveScores(array $items): void
    {
        if (empty($items)) {
            return;
        }

        $nowManila = Carbon::now('Asia/Manila');
        $userId = Auth::id() ?: 1;

        $ids = array_filter(array_map(fn($i) => (int) ($i['id'] ?? 0), $items));
        if (empty($ids)) {
            return;
        }

        $existingRecords = DB::table('ipc_sem_targets_indicator_itemlist')
            ->whereIn('id', $ids)
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

                $dbAverage = is_numeric($avgRaw) ? round((float) $avgRaw, 2) : null;
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
                    'average' => $dbAverage !== null ? number_format($dbAverage, 2, '.', '') : (($isQNa || $isQlNa || $isTNa) && $dbAverage === null ? 'N/A' : ''),
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
            } else {
                Flux::toast(
                    variant: 'info',
                    heading: __('No Changes'),
                    text: __('No changes were detected in the target scores or details.'),
                    position: 'top right'
                );
            }
        } catch (\Throwable $e) {
            // Ignore toast outside Livewire HTTP lifecycle
        }
    }

    public function render(): View
    {
        $categories = $this->categories();
        $visibleCategories = $this->categoryFilter === ''
            ? $categories
            : $categories->where('value', $this->categoryFilter)->values();

        return view('livewire.pages.semestral-target-page', [
            'semestralTargets' => $this->semestralTargets(),
            'categories' => $categories,
            'visibleCategories' => $visibleCategories,
            'semesterHeading' => $this->semesterHeading(),
        ]);
    }
}
