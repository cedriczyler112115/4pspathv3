<?php

namespace App\Http\Controllers\Inertia\Administration\Adjustment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ModifyTargetController extends Controller
{
    /**
     * Display the Modify Target adjustment page.
     */
    public function index(Request $request): Response
    {
        $targetId = trim((string) $request->input('target_id', ''));

        if ($targetId === '' || ! is_numeric($targetId)) {
            return Inertia::render('Administration/Adjustment/ModifyTarget', [
                'filters' => [
                    'target_id' => $targetId,
                ],
                'targets' => [
                    'data' => [],
                    'total' => 0,
                ],
                'categories' => [
                    ['value' => '1', 'label' => 'Strategic Function'],
                    ['value' => '2', 'label' => 'Core Function'],
                    ['value' => '3', 'label' => 'Support Function'],
                ],
                'semesters' => [
                    ['value' => '1', 'label' => '1st Semester'],
                    ['value' => '2', 'label' => '2nd Semester'],
                ],
            ]);
        }

        $items = DB::table('ipc_sem_targets_indicator as sti')
            ->join('ipc_sem_targets_indicator_itemlist as stii', 'stii.sem_target_id', '=', 'sti.id')
            ->leftJoin('ipc_semester as sem', 'sti.semester_id', '=', 'sem.id')
            ->leftJoin('users as u', 'sem.user_id', '=', 'u.id')
            ->where('sti.id', (int) $targetId)
            ->select([
                'sti.id as target_id',
                'sti.kra_category',
                'sti.activity',
                'sti.semester_id as rating_id',
                'sti.display_order as target_order',
                'stii.id as item_id',
                'stii.sem_target_id',
                'stii.new_semester',
                'stii.description',
                'stii.rg_quantity',
                'stii.rg_quality',
                'stii.rg_timeliness',
                'stii.rg_movs',
                'stii.rg_remarks',
                'sem.year as target_year',
                'sem.semester as rating_semester',
                'sem.lock as rating_lock',
                'u.id as user_id',
                DB::raw("TRIM(CONCAT_WS(' ', u.first_name, u.middle_name, u.last_name, u.extension_name)) as staff_name"),
                'u.email as staff_email',
            ])
            ->orderBy('stii.id')
            ->get();

        $records = $items->map(function ($item) {
            return [
                'target_id' => (int) $item->target_id,
                'kra_category' => (int) $item->kra_category,
                'activity' => (string) ($item->activity ?? ''),
                'rating_id' => $item->rating_id ? (int) $item->rating_id : null,
                'item_id' => (int) $item->item_id,
                'new_semester' => (int) ($item->new_semester ?? 1),
                'description' => (string) ($item->description ?? ''),
                'rg_quantity' => (string) ($item->rg_quantity ?? ''),
                'rg_quality' => (string) ($item->rg_quality ?? ''),
                'rg_timeliness' => (string) ($item->rg_timeliness ?? ''),
                'rg_movs' => (string) ($item->rg_movs ?? ''),
                'rg_remarks' => (string) ($item->rg_remarks ?? ''),
                'target_year' => $item->target_year,
                'rating_semester' => $item->rating_semester,
                'rating_lock' => $item->rating_lock,
                'staff_name' => $item->staff_name ?: 'N/A',
                'staff_email' => $item->staff_email,
            ];
        })->all();

        return Inertia::render('Administration/Adjustment/ModifyTarget', [
            'filters' => [
                'target_id' => $targetId,
            ],
            'targets' => [
                'data' => $records,
                'total' => count($records),
            ],
            'categories' => [
                ['value' => '1', 'label' => 'Strategic Function'],
                ['value' => '2', 'label' => 'Core Function'],
                ['value' => '3', 'label' => 'Support Function'],
            ],
            'semesters' => [
                ['value' => '1', 'label' => '1st Semester'],
                ['value' => '2', 'label' => '2nd Semester'],
            ],
        ]);
    }

    /**
     * Update an entire target row (both indicator and itemlist).
     */
    public function updateRow(Request $request, int $itemId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'target_id' => ['required', 'integer'],
            'kra_category' => ['required', 'integer', 'in:1,2,3'],
            'activity' => ['required', 'string'],
            'new_semester' => ['required', 'integer', 'in:1,2'],
            'description' => ['required', 'string'],
            'rg_quantity' => ['nullable', 'string'],
            'rg_quality' => ['nullable', 'string'],
            'rg_timeliness' => ['nullable', 'string'],
            'rg_movs' => ['nullable', 'string'],
            'rg_remarks' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($validated, $itemId) {
            DB::table('ipc_sem_targets_indicator')
                ->where('id', $validated['target_id'])
                ->update([
                    'kra_category' => $validated['kra_category'],
                    'activity' => $validated['activity'],
                ]);

            DB::table('ipc_sem_targets_indicator_itemlist')
                ->where('id', $itemId)
                ->where('sem_target_id', $validated['target_id'])
                ->update([
                    'new_semester' => $validated['new_semester'],
                    'description' => $validated['description'],
                    'rg_quantity' => $validated['rg_quantity'] ?? '',
                    'rg_quality' => $validated['rg_quality'] ?? '',
                    'rg_timeliness' => $validated['rg_timeliness'] ?? '',
                    'rg_movs' => $validated['rg_movs'] ?? '',
                    'rg_remarks' => $validated['rg_remarks'] ?? '',
                ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Target updated successfully.',
            ]);
        }

        return back()->with('success', 'Target updated successfully.');
    }

    /**
     * Update target indicator fields.
     */
    public function updateIndicator(Request $request, int $targetId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'kra_category' => ['sometimes', 'integer', 'in:1,2,3'],
            'activity' => ['sometimes', 'string'],
        ]);

        DB::table('ipc_sem_targets_indicator')
            ->where('id', $targetId)
            ->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Indicator updated successfully.',
            ]);
        }

        return back()->with('success', 'Indicator updated successfully.');
    }

    /**
     * Update indicator itemlist item fields.
     */
    public function updateItem(Request $request, int $itemId): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'new_semester' => ['sometimes', 'integer', 'in:1,2'],
            'description' => ['sometimes', 'string'],
            'rg_quantity' => ['nullable', 'string'],
            'rg_quality' => ['nullable', 'string'],
            'rg_timeliness' => ['nullable', 'string'],
            'rg_movs' => ['nullable', 'string'],
            'rg_remarks' => ['nullable', 'string'],
        ]);

        DB::table('ipc_sem_targets_indicator_itemlist')
            ->where('id', $itemId)
            ->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully.',
            ]);
        }

        return back()->with('success', 'Item updated successfully.');
    }
}
