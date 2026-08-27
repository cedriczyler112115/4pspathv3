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

    public bool $showMovTransferModal = false;

    public ?int $attachmentItemId = null;

    public ?int $movTransferItemId = null;

    /** @var array<int, string> */
    public array $movSelectedAttachments = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $attachmentFiles = [];

    /** @var array<int, array{name:string,path:string,type:string,size:string}> */
    public array $existingAttachments = [];

    public int $attachmentUploadProgress = 0;

    public string $movTransferUserId = '';

    public string $movTransferSearch = '';

    public string $movTransferContextYear = '';

    public string $movTransferContextSemester = '';

    protected const ATTACHMENT_DIRECTORY = 'uploaded_movs';

    /** @var array<int, array{quantity_score:string, quality_score:string, timeliness_score:string, average:string}> */
    public array $scores = [];

    public bool $isSemesterLocked = false;

    public int $semLock = 0;

    public ?string $dateVerified = null;

    /** @param array<int, array<string, mixed>> $rows */
    public function mount(int $indicatorId, array $rows, bool $isSemesterLocked = false, int $semLock = 0, ?string $dateVerified = null): void
    {
        $this->indicatorId = $indicatorId;
        $this->rows = $rows;
        $this->isSemesterLocked = $isSemesterLocked;
        $this->semLock = $semLock;
        $this->dateVerified = $dateVerified;
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
                $semRecord = DB::table('ipc_semester')->where('id', $semId)->first();
                $this->isSemesterLocked = ((int) ($semRecord->lock ?? 0) !== 0);
            } else {
                $this->isSemesterLocked = false;
            }
        }

    }

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

        $semRecord = DB::table('ipc_semester')->where('id', $semesterId)->first();
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
        if (! $this->canModifySemester()) {
            return;
        }

        $firstRow = $this->rows[0] ?? null;

        if ($firstRow === null) {
            return;
        }

        $editableRows = [];
        foreach ($this->rows as $row) {
            $itemId = (int) ($row['sem_item_id'] ?? 0);
            if ($itemId > 0) {
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

    public function openMovTransferModal(int $itemId): void
    {
        if ($itemId <= 0) {
            return;
        }

        $item = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $itemId)
            ->where('sem.user_id', Auth::id())
            ->select(['itl.id as item_id', 'sem.year', 'sem.semester'])
            ->first();

        if (! $item) {
            Flux::toast(variant: 'danger', text: __('Unable to open MOV transfer for this target.'));

            return;
        }

        $this->movTransferItemId = $itemId;
        $this->movTransferUserId = '';
        $this->movTransferSearch = '';
        $this->movTransferContextYear = (string) ($item->year ?? now()->year);
        $this->movTransferContextSemester = (string) ($item->semester ?? (now()->month >= 7 ? 2 : 1));
        $this->showMovTransferModal = true;
    }

    public function closeMovTransferModal(): void
    {
        $this->showMovTransferModal = false;
        $this->movTransferItemId = null;
        $this->movTransferUserId = '';
        $this->movTransferSearch = '';
        $this->movTransferContextYear = '';
        $this->movTransferContextSemester = '';
        $this->movSelectedAttachments = [];
    }

    public function updatedMovSelectedAttachments(): void
    {
        $this->movSelectedAttachments = array_values(array_unique(array_filter(
            array_map('strval', $this->movSelectedAttachments),
            static fn (string $value): bool => $value !== ''
        )));
    }

    public function selectAllMovAttachments(): void
    {
        $this->movSelectedAttachments = $this->movTransferAttachmentFilenames();
    }

    public function clearSelectedMovAttachments(): void
    {
        $this->movSelectedAttachments = [];
    }

    public function toggleSelectAllMovAttachmentsForItem(int $itemId): void
    {
        if ($itemId <= 0) {
            return;
        }

        $attachments = $this->movTransferItemAttachments($itemId);
        $filenames = array_values(array_filter(array_map(
            static fn (array $attachment): string => (string) ($attachment['filename'] ?? ''),
            $attachments
        )));

        if (empty($filenames)) {
            return;
        }

        $allSelected = empty(array_diff($filenames, $this->movSelectedAttachments));

        if ($allSelected) {
            $this->movSelectedAttachments = array_values(array_diff($this->movSelectedAttachments, $filenames));
            return;
        }

        $this->movSelectedAttachments = array_values(array_unique(array_merge($this->movSelectedAttachments, $filenames)));
    }

    /** @return array<int, array{name:string,path:string,type:string,size:string,filename:string}> */
    public function movTransferItemAttachments(int $itemId): array
    {
        if ($itemId <= 0) {
            return [];
        }

        return $this->attachmentsForItem($itemId);
    }

    /** @return array<int, string> */
    public function movTransferAttachmentFilenames(): array
    {
        $attachments = [];

        foreach ($this->staffMovSources() as $items) {
            foreach ($items as $sourceItem) {
                foreach ($this->attachmentsForItem((int) ($sourceItem->item_id ?? 0)) as $attachment) {
                    $filename = (string) ($attachment['filename'] ?? '');
                    if ($filename !== '') {
                        $attachments[] = $filename;
                    }
                }
            }
        }

        return array_values(array_unique($attachments));
    }

    /** @return Collection<int, object> */
    public function movTransferUsers(): Collection
    {
        $currentUserId = Auth::id();
        if (! is_int($currentUserId)) {
            return collect();
        }

        return DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->join('users as u', 'sem.user_id', '=', 'u.id')
            ->where('sem.user_id', '!=', $currentUserId)
            ->select(['u.id', 'u.first_name', 'u.middle_name', 'u.last_name', 'u.position'])
            ->distinct()
            ->orderBy('u.last_name')
            ->orderBy('u.first_name')
            ->get()
            ->map(function (object $u): object {
                $u->full_name = mb_strtoupper(trim(($u->last_name ?? '') . (filled($u->last_name) ? ', ' : '') . collect([$u->first_name, $u->middle_name])->filter()->join(' ')), 'UTF-8');

                return $u;
        });
    }

    public function cancelAttachmentUpload(): void
    {
        $this->showAttachmentModal = false;
        $this->attachmentItemId = null;
        $this->attachmentFiles = [];
        $this->existingAttachments = [];
        $this->attachmentUploadProgress = 0;
    }

    public function updatedMovTransferUserId(): void { }
    public function updatedMovTransferSearch(): void { }

    public function applyMovTransferSearch(): void
    {
        $this->movTransferSearch = trim($this->movTransferSearch);
    }

    public function staffMovSources(): Collection
    {
        $userId = Auth::id();
        if (! is_int($userId)) {
            return collect();
        }

        $query = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as itl', 'itl.sem_target_id', '=', 'sti.id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->join('users as u', 'sem.user_id', '=', 'u.id')
            ->where('sem.user_id', '!=', $userId)
            ->where('itl.has_attachments', 1);

        if ($this->movTransferContextYear !== '') {
            $query->where('sem.year', $this->movTransferContextYear);
        }

        if ($this->movTransferContextSemester !== '') {
            $query->where('sem.semester', (int) $this->movTransferContextSemester);
        }

        if ($this->movTransferItemId) {
            $itemContext = DB::table('ipc_sem_targets_indicator_itemlist as itl')
                ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
                ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
                ->where('itl.id', $this->movTransferItemId)
                ->select(['sem.year', 'sem.semester'])
                ->first();

            if ($itemContext) {
                $query->where('sem.year', $itemContext->year)
                    ->where('sem.semester', $itemContext->semester);
            }
        }

        if ($this->movTransferUserId === '') {
            return collect();
        }

        $query->where('sem.user_id', (int) $this->movTransferUserId);

        if (trim($this->movTransferSearch) !== '') {
            $search = '%' . trim($this->movTransferSearch) . '%';
            $query->where(function ($q) use ($search): void {
                $q->where('sti.activity', 'like', $search)
                    ->orWhere('itl.description', 'like', $search)
                    ->orWhere('u.first_name', 'like', $search)
                    ->orWhere('u.last_name', 'like', $search)
                    ->orWhere('u.middle_name', 'like', $search);
            });
        }

        return $query->select([
            'sti.id as sem_target_id',
            'sti.activity',
            'sti.kra_category',
            'sem.year',
            'sem.semester',
            'sem.user_id',
            'u.first_name',
            'u.middle_name',
            'u.last_name',
            'itl.id as item_id',
            'itl.description',
            'itl.rg_movs',
        ])->orderBy('u.last_name')->orderBy('u.first_name')->orderBy('sem.year', 'desc')->get()->groupBy('sem_target_id');
    }

    public function copyStaffMovsToTarget(int $sourceItemId): void
    {
        $userId = Auth::id();
        $destItemId = $this->movTransferItemId;

        if (! is_int($userId) || $destItemId === null || $destItemId <= 0 || $sourceItemId <= 0) {
            return;
        }

        $destOwned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $destItemId)
            ->where('sem.user_id', $userId)
            ->exists();

        $sourceOwned = DB::table('ipc_sem_targets_indicator_itemlist as itl')
            ->join('ipc_sem_targets_indicator as sti', 'sti.id', '=', 'itl.sem_target_id')
            ->join('ipc_semester as sem', 'sem.id', '=', 'sti.semester_id')
            ->where('itl.id', $sourceItemId)
            ->where('sem.user_id', '!=', $userId)
            ->exists();

        if (! $destOwned || ! $sourceOwned) {
            Flux::toast(variant: 'danger', text: __('Unable to copy MOVs from the selected staff target.'));

            return;
        }

        $sourceAttachments = $this->attachmentsForItem($sourceItemId);

        if (empty($sourceAttachments)) {
            Flux::toast(variant: 'warning', text: __('No MOVs were found for the selected source target.'));

            return;
        }

        $now = Carbon::now('Asia/Manila');
        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);
        File::ensureDirectoryExists($uploadDir);

        $storedPaths = [];

        try {
            foreach ($sourceAttachments as $index => $attachment) {
                $sourcePath = public_path($attachment['path']);
                if (! File::exists($sourcePath)) {
                    continue;
                }

                $extension = strtolower(pathinfo($attachment['filename'], PATHINFO_EXTENSION));
                $baseName = pathinfo($attachment['name'], PATHINFO_FILENAME);
                $safeBase = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $baseName) ?: 'mov';
                $safeBase = trim(substr($safeBase, 0, 80), '_-') ?: 'mov';
                $newFileName = $destItemId . '_' . $safeBase . '_' . $now->format('YmdHis') . '_' . ($index + 1) . '.' . $extension;
                $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

                if (! File::copy($sourcePath, $destinationPath)) {
                    throw new \RuntimeException('Unable to copy MOV file.');
                }

                $storedPaths[] = $destinationPath;
            }

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $destItemId)
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
            Flux::toast(variant: 'danger', text: __('The MOVs could not be copied. Please try again.'));

            return;
        }

        request()->attributes->remove('_semestral_mov_attachment_counts_' . $this->attachmentDirectorySignature());
        $attachmentCounts = $this->attachmentCountMap();
        $this->existingAttachments = $this->attachmentsForItem($destItemId);

        foreach ($this->rows as &$row) {
            if ((int) ($row['sem_item_id'] ?? 0) !== $destItemId) {
                continue;
            }

            $row['has_attachments'] = 1;
            $row['attachment_count'] = $attachmentCounts[$destItemId] ?? count($this->existingAttachments);
        }
        unset($row);

        Flux::toast(variant: 'success', text: __('MOVs copied successfully.'));
        $this->movSelectedAttachments = [];
        $this->closeMovTransferModal();
        $this->dispatch('semestral-target-updated');
    }

    public function movTransferAttachmentCount(int $itemId): int
    {
        return count($this->attachmentsForItem($itemId));
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
        $cacheKey = '_semestral_mov_attachment_counts_' . $this->attachmentDirectorySignature();
        if (request()->attributes->has($cacheKey)) {
            return request()->attributes->get($cacheKey);
        }

        $counts = [];
        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);
        if (File::isDirectory($uploadDir)) {
            foreach (File::files($uploadDir) as $file) {
                if (preg_match('/^(\d+)_/i', $file->getFilename(), $matches) !== 1) {
                    continue;
                }

                $itemId = (int) $matches[1];
                $counts[$itemId] = ($counts[$itemId] ?? 0) + 1;
            }
        }

        request()->attributes->set($cacheKey, $counts);

        return $counts;
    }

    protected function attachmentDirectorySignature(): string
    {
        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);

        if (! File::isDirectory($uploadDir)) {
            return 'empty';
        }

        $signatureParts = [];
        foreach (File::files($uploadDir) as $file) {
            $signatureParts[] = $file->getFilename() . ':' . $file->getMTime() . ':' . $file->getSize();
        }

        return sha1(implode('|', $signatureParts));
    }

    /** @return array<int, array{name:string,path:string,type:string,size:string}> */
    protected function attachmentsForItem(int $itemId): array
    {
        $uploadDir = public_path(self::ATTACHMENT_DIRECTORY);
        if (! File::isDirectory($uploadDir)) {
            return [];
        }

        $pattern = '/^' . preg_quote((string) $itemId, '/') . '_/i';

        return collect(File::files($uploadDir))
            ->filter(fn ($file): bool => preg_match($pattern, $file->getFilename()) === 1)
            ->sortByDesc(fn ($file): int => $file->getMTime())
            ->map(function ($file): array {
                $extension = strtolower($file->getExtension());
                $displayName = $file->getFilename();

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

        $pattern = '/^' . preg_quote((string) $itemId, '/') . '_/i';
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

        if (! $this->canModifySemester()) {
            $this->cancel();

            return;
        }

        if ($this->creatingSubTarget) {
            $this->validate([
                'pendingSubTargets' => ['required', 'array', 'min:1'],
                'pendingSubTargets.*.description' => ['required', 'string', 'max:10000'],
                'pendingSubTargets.*.quantity' => ['nullable', 'string', 'max:10000'],
                'pendingSubTargets.*.quality' => ['nullable', 'string', 'max:10000'],
                'pendingSubTargets.*.timeliness' => ['nullable', 'string', 'max:10000'],
                'pendingSubTargets.*.movs' => ['nullable', 'string', 'max:10000'],
                'pendingSubTargets.*.remarks' => ['nullable', 'string', 'max:10000'],
            ], [
                'pendingSubTargets.*.description.required' => __('Success Indicator is required.'),
            ]);
        } else {
            $this->validate([
                'editActivity' => ['required', 'string', 'max:10000'],
                'editCategory' => ['required', 'integer', 'min:1'],
                'editRows' => ['required', 'array', 'min:1'],
                'editRows.*.description' => ['required', 'string', 'max:10000'],
                'editRows.*.quantity' => ['nullable', 'string', 'max:10000'],
                'editRows.*.quality' => ['nullable', 'string', 'max:10000'],
                'editRows.*.timeliness' => ['nullable', 'string', 'max:10000'],
                'editRows.*.movs' => ['nullable', 'string', 'max:10000'],
                'editRows.*.remarks' => ['nullable', 'string', 'max:10000'],
            ], [
                'editActivity.required' => __('Key Result Area is required.'),
                'editRows.*.description.required' => __('Success Indicator is required.'),
            ]);
        }

        $is2026Sem2 = $this->is2026SecondSemesterOrBeyond();
        $requiresJustification = $this->creatingSubTarget || $is2026Sem2;

        if ($this->creatingSubTarget && ! $this->showJustificationModal) {
            $this->justificationText = '';
            $this->showJustificationModal = true;

            return;
        }

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

        if ($requiresJustification && empty(trim($this->justificationText))) {
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
        if (! $this->canModifySemester()) {
            return;
        }

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
        if (! $this->canModifySemester()) {
            return;
        }

        $this->dispatch('semestral-target-delete-requested', semTargetId: $this->indicatorId);
    }

    public function requestDeleteSubTarget(int $itemId): void
    {
        if ($itemId > 0 && $this->canModifySemester()) {
            $this->dispatch('semestral-target-subtarget-delete-requested', semItemId: $itemId);
        }
    }

    protected function canModifySemester(): bool
    {
        $semester = DB::table('ipc_sem_targets_indicator as target')
            ->join('ipc_semester as semester', 'semester.id', '=', 'target.semester_id')
            ->where('target.id', $this->indicatorId)
            ->where('semester.user_id', Auth::id())
            ->select(['semester.id', 'semester.lock'])
            ->first();

        $canModify = $semester !== null && (int) ($semester->lock ?? 0) === 0;
        $this->isSemesterLocked = ! $canModify;
        $this->semLock = (int) ($semester->lock ?? 1);

        if (! $canModify) {
            Flux::toast(variant: 'danger', text: __('This semester is locked. Target changes are no longer allowed.'));
        }

        return $canModify;
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
