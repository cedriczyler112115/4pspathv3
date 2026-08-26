<?php

namespace App\Livewire\SemestralTarget;

use Flux\Flux;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Livewire\Component;

class IndicatorRows extends Component
{
    use WithFileUploads;

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

    public bool $showAttachmentModal = false;

    public ?int $attachmentItemId = null;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var array<int, array{name:string,path:string,type:string,size:string}> */
    public array $existingAttachments = [];

    public int $attachmentUploadProgress = 0;

    protected const ATTACHMENT_DIRECTORY = 'uploaded_movs';

    /** @var array<int, array{quantity_score:string, quality_score:string, timeliness_score:string, average:string}> */
    public array $scores = [];

    public bool $isSemesterLocked = false;

    /** @param array<int, array<string, mixed>> $rows */
    public function mount(int $indicatorId, array $rows, bool $isSemesterLocked = false): void
    {
        $this->indicatorId = $indicatorId;
        $this->rows = $rows;
        $attachmentCounts = $this->attachmentCountMap();
        foreach ($this->rows as &$row) {
            $itemId = (int) ($row['sem_item_id'] ?? 0);
            $row['attachment_count'] = $attachmentCounts[$itemId] ?? 0;
        }
        unset($row);

        if ($isSemesterLocked) {
            $this->isSemesterLocked = true;
        } else {
            $semId = request()->query('sem_id');
            if (!$semId && !empty($rows[0]['semester_id'])) {
                $semId = (int) $rows[0]['semester_id'];
            }

            if ($semId) {
                if (!array_key_exists($semId, static::$semesterRecordCache)) {
                    static::$semesterRecordCache[$semId] = DB::table('ipc_semester')->where('id', $semId)->first();
                }
                $semRecord = static::$semesterRecordCache[$semId];
                $this->isSemesterLocked = ((int) ($semRecord->lock ?? 0) === 1);
            } else {
                $this->isSemesterLocked = false;
            }
        }

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
        $this->cancelAttachmentUpload();
    }

    public function openAttachmentUpload(int $itemId): void
    {
        if ($itemId <= 0) {
            return;
        }

        $this->attachmentItemId = $itemId;
        $this->attachmentFiles = [];
        $this->attachmentUploadProgress = 0;
        $this->existingAttachments = $this->attachmentsForItem($itemId);
        $this->showAttachmentModal = true;
    }

    public function cancelAttachmentUpload(): void
    {
        $this->showAttachmentModal = false;
        $this->attachmentItemId = null;
        $this->attachmentFiles = [];
        $this->existingAttachments = [];
        $this->attachmentUploadProgress = 0;
    }

    public function removeQueuedAttachment(int $index): void
    {
        if (isset($this->attachmentFiles[$index])) {
            array_splice($this->attachmentFiles, $index, 1);
            $this->attachmentFiles = array_values($this->attachmentFiles);
        }
    }

    public function saveAttachmentUploads(): void
    {
        $userId = Auth::id();
        $itemId = $this->attachmentItemId;

        if ($userId === null || $itemId === null || $itemId <= 0) {
            return;
        }

        $owned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $itemId)
            ->where('sem.user_id', $userId)
            ->exists();

        if (! $owned) {
            Flux::toast(variant: 'danger', text: __('Unable to upload attachments for this target.'));

            return;
        }

        $validated = $this->validate([
            'attachmentFiles' => ['required', 'array', 'min:1', 'max:20'],
            'attachmentFiles.*' => ['file', 'mimes:jpg,jpeg,png,pdf,jfif,webp', 'max:10240'],
        ], [
            'attachmentFiles.required' => __('Please choose at least one file to upload.'),
            'attachmentFiles.*.mimes' => __('Only JPG, PNG, WEBP, JFIF, and PDF files are allowed.'),
            'attachmentFiles.*.max' => __('Each file must be 10MB or smaller.'),
        ]);

        $files = $validated['attachmentFiles'] ?? [];
        if (! is_array($files) || empty($files)) {
            return;
        }

        $now = Carbon::now('Asia/Manila');
        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);
        File::ensureDirectoryExists($uploadDir);

        $storedPaths = [];

        try {
            foreach ($files as $index => $file) {
                if (! $file instanceof UploadedFile) {
                    continue;
                }

                $extension = strtolower((string) $file->getClientOriginalExtension());
                $baseName = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeBase = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $baseName) ?: 'mov';
                $safeBase = trim(substr($safeBase, 0, 80), '_-') ?: 'mov';
                $fileName = $itemId . '_' . $safeBase . '_' . $now->format('YmdHis') . '_' . ($index + 1) . '.' . $extension;
                $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
                $temporaryPath = $file->getRealPath();

                // Copying avoids Windows file-lock failures caused by moving Livewire temp files.
                if ($temporaryPath === false || ! File::copy($temporaryPath, $destinationPath)) {
                    throw new \RuntimeException('Unable to copy the uploaded MOV file.');
                }

                $storedPaths[] = $destinationPath;
            }

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $itemId)
                ->update([
                    'has_attachments' => 1,
                    'modified_by' => $userId,
                    'date_modified' => $now,
                ]);
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $storedPath) {
                File::delete($storedPath);
            }

            report($exception);
            Flux::toast(variant: 'danger', text: __('The attachments could not be saved. Please try again.'));

            return;
        }

        request()->attributes->remove('_semestral_mov_attachment_counts');
        $attachmentCounts = $this->attachmentCountMap();
        $this->existingAttachments = $this->attachmentsForItem($itemId);
        foreach ($this->rows as &$row) {
            if ((int) ($row['sem_item_id'] ?? 0) !== $itemId) {
                continue;
            }

            $row['has_attachments'] = 1;
            $row['attachment_count'] = $attachmentCounts[$itemId] ?? count($this->existingAttachments);
        }
        unset($row);

        $this->attachmentFiles = [];
        $this->attachmentUploadProgress = 0;
        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Attachments uploaded successfully.'));
    }

    /** @return array<int, int> */
    protected function attachmentCountMap(): array
    {
        $cacheKey = '_semestral_mov_attachment_counts';
        if (request()->attributes->has($cacheKey)) {
            return request()->attributes->get($cacheKey);
        }

        $counts = [];
        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);
        if (File::isDirectory($uploadDir)) {
            foreach (File::files($uploadDir) as $file) {
                if (preg_match('/^(\d+)_.*_\d{14}_\d+\.([a-z0-9]+)$/i', $file->getFilename(), $matches) !== 1) {
                    continue;
                }

                $itemId = (int) $matches[1];
                $counts[$itemId] = ($counts[$itemId] ?? 0) + 1;
            }
        }

        request()->attributes->set($cacheKey, $counts);

        return $counts;
    }

    /** @return array<int, array{name:string,path:string,type:string,size:string}> */
    protected function attachmentsForItem(int $itemId): array
    {
        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);
        if (! File::isDirectory($uploadDir)) {
            return [];
        }

        $pattern = '/^' . preg_quote((string) $itemId, '/') . '_(.+)_(\d{14})_(\d+)\.([a-z0-9]+)$/i';

        return collect(File::files($uploadDir))
            ->filter(fn ($file): bool => preg_match($pattern, $file->getFilename()) === 1)
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->map(function ($file) use ($pattern): array {
                $extension = strtolower($file->getExtension());
                $displayName = $file->getFilename();
                if (preg_match($pattern, $displayName, $matches) === 1) {
                    $originalName = trim((string) ($matches[1] ?? ''), '_-');
                    $displayName = $originalName !== '' ? $originalName . '.' . $extension : $displayName;
                }

                return [
                    'name' => $displayName,
                    'path' => self::ATTACHMENT_DIRECTORY . '/' . $file->getFilename(),
                    'filename' => $file->getFilename(),
                    'url' => asset(self::ATTACHMENT_DIRECTORY . '/' . $file->getFilename()),
                    'type' => $extension === 'pdf' ? 'pdf' : 'image',
                    'size' => number_format($file->getSize() / 1024 / 1024, 2) . ' MB',
                ];
            })
            ->values()
            ->all();
    }

    public function deleteAttachment(string $filename): void
    {
        $userId = Auth::id();
        $itemId = $this->attachmentItemId;

        if ($userId === null || $itemId === null || $itemId <= 0 || empty($filename)) {
            return;
        }

        $owned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $itemId)
            ->where('sem.user_id', $userId)
            ->exists();

        if (! $owned) {
            Flux::toast(variant: 'danger', text: __('Unable to delete attachments for this target.'));

            return;
        }

        $pattern = '/^' . preg_quote((string) $itemId, '/') . '_(.+)_(\d{14})_(\d+)\.([a-z0-9]+)$/i';
        if (preg_match($pattern, $filename) !== 1) {
            Flux::toast(variant: 'danger', text: __('Invalid attachment file specified.'));

            return;
        }

        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);
        $filePath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        request()->attributes->remove('_semestral_mov_attachment_counts');
        $now = Carbon::now('Asia/Manila');
        $remainingAttachments = $this->attachmentsForItem($itemId);
        $hasAttachments = count($remainingAttachments) > 0 ? 1 : null;

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->update([
                'has_attachments' => $hasAttachments,
                'modified_by' => $userId,
                'date_modified' => $now,
            ]);

        $this->existingAttachments = $remainingAttachments;

        foreach ($this->rows as &$row) {
            if ((int) ($row['sem_item_id'] ?? 0) !== $itemId) {
                continue;
            }

            $row['has_attachments'] = $hasAttachments;
            $row['attachment_count'] = count($remainingAttachments);
        }
        unset($row);

        $this->dispatch('semestral-target-updated');
        Flux::toast(variant: 'success', text: __('Attachment removed successfully.'));
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
                'stii.has_attachments',
                'stii.average',
                'stii.actual_accomp',
                'stii.target_movs',
                'stii.target_remarks',
            ])
            ->get();

        if ($dbRows->isNotEmpty()) {
            $attachmentCounts = $this->attachmentCountMap();
            $this->rows = $dbRows->map(function ($r) use ($attachmentCounts): array {
                $arr = (array) $r;
                $itemId = (int) ($arr['sem_item_id'] ?? 0);
                $arr['attachment_count'] = $attachmentCounts[$itemId] ?? 0;

                return $arr;
            })->all();
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
                $movs = $row['target_movs'] ?? null;
                $rem = $row['target_remarks'] ?? null;

                // Fallback to 'N/A' text if na_quantity = 1, na_quality = 1, or na_timeliness = 1
                $qStr = $naQ === 1 ? 'N/A' : ($q !== null && $q !== '' ? (string) $q : '');
                $qlStr = $naQl === 1 ? 'N/A' : ($ql !== null && $ql !== '' ? (string) $ql : '');
                $tStr = $naT === 1 ? 'N/A' : ($t !== null && $t !== '' ? (string) $t : '');

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

        $semesterId = (int) ($this->rows[0]['semester_id'] ?? 0);
        if ($semesterId > 0) {
            $historyCacheKey = 'semestral_target_history_'.$semesterId;
            $semesterHistories = request()->attributes->get($historyCacheKey);

            if (! $semesterHistories instanceof Collection) {
                $semesterHistories = DB::table('ipc_sem_target_edit_histories as history')
                    ->join('ipc_sem_targets_indicator as target', 'target.id', '=', 'history.sem_target_id')
                    ->where('target.semester_id', $semesterId)
                    ->select(['history.sem_target_id', 'history.sem_item_id', 'history.field_name'])
                    ->get();
                request()->attributes->set($historyCacheKey, $semesterHistories);
            }

            $historyRecords = $semesterHistories
                ->filter(fn ($record): bool => (int) $record->sem_target_id === $this->indicatorId
                    || in_array((int) $record->sem_item_id, $itemIds, true));
        } else {
            $historyRecords = DB::table('ipc_sem_target_edit_histories')
                ->where(function ($query) use ($itemIds) {
                    $query->where('sem_target_id', $this->indicatorId);
                    if (! empty($itemIds)) {
                        $query->orWhereIn('sem_item_id', $itemIds);
                    }
                })
                ->select(['sem_target_id', 'sem_item_id', 'field_name'])
                ->get();
        }

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

        $isTargetNewlyAdded = $historyRecords->contains(fn ($record): bool => $record->field_name === 'created'
            && (int) $record->sem_target_id === $this->indicatorId
            && empty($record->sem_item_id));

        return view('livewire.semestral-target.indicator-rows', [
            'categories' => $categories,
            'hasGroupHistory' => $hasGroupHistory,
            'hasHistoryByItem' => $hasHistoryByItem,
            'isTargetNewlyAdded' => $isTargetNewlyAdded,
            'isSemesterLocked' => $this->isSemesterLocked,
        ]);
    }

    protected function normalizeTextareaValue(mixed $value): string
    {
        $text = html_entity_decode((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return preg_replace('/<br\s*\/?>/i', "\n", $text) ?? '';
    }
}
