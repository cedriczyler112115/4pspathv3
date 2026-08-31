<?php

namespace App\Http\Controllers\Inertia\Libraries;

use App\Http\Controllers\Controller;
use App\Models\LibHarmonizedPosition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class HarmonizedStaffController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $query = LibHarmonizedPosition::query();
        if (filled($filters['search'])) {
            $query->where('name', 'like', '%'.trim($filters['search']).'%');
        }

        $positions = $query
            ->orderBy('sort_order', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($filters['perPage']);

        $maxSort = (int) LibHarmonizedPosition::query()->max('sort_order');

        return Inertia::render('Libraries/HarmonizedStaff', [
            'filters' => $filters,
            'positions' => [
                'data' => array_map(fn ($position) => [
                    'id' => (int) $position->id,
                    'name' => (string) $position->name,
                    'sortOrder' => (int) $position->sort_order,
                    'isActive' => (bool) $position->is_active,
                ], $positions->items()),
                'from' => $positions->firstItem(),
                'to' => $positions->lastItem(),
                'total' => $positions->total(),
                'currentPage' => $positions->currentPage(),
                'lastPage' => $positions->lastPage(),
            ],
            'maxSortOrder' => $maxSort + 1,
            'perPageOptions' => [
                ['value' => 10, 'label' => '10'],
                ['value' => 20, 'label' => '20'],
                ['value' => 50, 'label' => '50'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('lib_harmonized_positions', 'name')],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['required', 'boolean'],
        ]);

        LibHarmonizedPosition::query()->create([
            'name' => $data['name'],
            'sort_order' => (int) $data['sortOrder'],
            'is_active' => (bool) $data['isActive'],
        ]);

        return back()->with('success', __('Position created successfully.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $position = LibHarmonizedPosition::query()->findOrFail($id);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('lib_harmonized_positions', 'name')->ignore($position->id)],
            'sortOrder' => ['required', 'integer', 'min:0'],
            'isActive' => ['required', 'boolean'],
        ]);

        $position->update([
            'name' => $data['name'],
            'sort_order' => (int) $data['sortOrder'],
            'is_active' => (bool) $data['isActive'],
        ]);

        return back()->with('success', __('Position updated successfully.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        LibHarmonizedPosition::query()->findOrFail($id)->delete();

        return back()->with('success', __('Position deleted successfully.'));
    }
}
