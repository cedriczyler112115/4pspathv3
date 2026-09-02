<?php

namespace App\Http\Controllers\Inertia\Libraries;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DivisionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $query = Division::query();

        if (filled($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search): void {
                $like = '%'.$search.'%';
                $q->where('division_name', 'like', $like)
                    ->orWhere('head_pos', 'like', $like);
            });
        }

        $divisions = $query
            ->orderBy('division_name')
            ->paginate($filters['perPage']);

        $people = User::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'extension_name'])
            ->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => trim(collect([$user->last_name, $user->first_name, $user->middle_name, $user->extension_name])->filter()->join(', ')),
            ])
            ->all();

        return Inertia::render('Libraries/Division', [
            'filters' => $filters,
            'divisions' => [
                'data' => array_map(fn ($division) => [
                    'id' => (int) $division->id,
                    'divisionName' => (string) $division->division_name,
                    'divisionHead' => (int) $division->division_head,
                    'divisionSignatory' => $division->division_signatory !== null ? (int) $division->division_signatory : null,
                    'divStatus' => (int) $division->div_status,
                    'headPos' => (string) $division->head_pos,
                ], $divisions->items()),
                'from' => $divisions->firstItem(),
                'to' => $divisions->lastItem(),
                'total' => $divisions->total(),
                'currentPage' => $divisions->currentPage(),
                'lastPage' => $divisions->lastPage(),
            ],
            'people' => $people,
            'perPageOptions' => [
                ['value' => 10, 'label' => '10'],
                ['value' => 20, 'label' => '20'],
                ['value' => 50, 'label' => '50'],
                ['value' => 100, 'label' => '100'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        Division::query()->create($data);

        return back()->with('success', __('Division created successfully.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $division = Division::query()->findOrFail($id);
        $data = $this->validatePayload($request, $division->id);

        $division->update($data);

        return back()->with('success', __('Division updated successfully.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        Division::query()->findOrFail($id)->delete();

        return back()->with('success', __('Division deleted successfully.'));
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'divisionName' => [
                'required',
                'string',
                'max:150',
                Rule::unique('lib_division', 'division_name')->ignore($ignoreId),
            ],
            'divisionHead' => ['required', 'integer', Rule::exists('users', 'id')],
            'divisionSignatory' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'divStatus' => ['required', 'integer', 'in:0,1'],
            'headPos' => ['required', 'string', 'max:150'],
        ]);

        return [
            'division_name' => $data['divisionName'],
            'division_head' => (int) $data['divisionHead'],
            'division_signatory' => filled($data['divisionSignatory'] ?? null) ? (int) $data['divisionSignatory'] : null,
            'div_status' => (int) $data['divStatus'],
            'head_pos' => $data['headPos'],
        ];
    }
}
