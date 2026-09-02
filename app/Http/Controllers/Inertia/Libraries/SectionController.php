<?php

namespace App\Http\Controllers\Inertia\Libraries;

use App\Http\Controllers\Controller;
use App\Models\Division;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SectionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => (string) $request->string('search'),
            'perPage' => (int) ($request->integer('perPage') ?: 10),
        ];

        $query = Section::query();

        if (filled($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search): void {
                $like = '%'.$search.'%';
                $q->where('section_name', 'like', $like)
                    ->orWhere('sec_acronym', 'like', $like);
            });
        }

        $sections = $query
            ->orderBy('section_name')
            ->paginate($filters['perPage']);

        $divisions = Division::query()
            ->orderBy('division_name')
            ->get(['id', 'division_name'])
            ->map(fn (Division $division) => [
                'id' => (int) $division->id,
                'name' => (string) $division->division_name,
            ])
            ->all();

        return Inertia::render('Libraries/Section', [
            'filters' => $filters,
            'sections' => [
                'data' => array_map(fn ($section) => [
                    'id' => (int) $section->id,
                    'sectionName' => (string) $section->section_name,
                    'divisionId' => (int) $section->division_id,
                    'secAcronym' => $section->sec_acronym !== null ? (string) $section->sec_acronym : null,
                    'secStatus' => (int) $section->sec_status,
                ], $sections->items()),
                'from' => $sections->firstItem(),
                'to' => $sections->lastItem(),
                'total' => $sections->total(),
                'currentPage' => $sections->currentPage(),
                'lastPage' => $sections->lastPage(),
            ],
            'divisions' => $divisions,
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

        Section::query()->create($data);

        return back()->with('success', __('Section created successfully.'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $section = Section::query()->findOrFail($id);
        $data = $this->validatePayload($request, $section->id);

        $section->update($data);

        return back()->with('success', __('Section updated successfully.'));
    }

    public function destroy(int $id): RedirectResponse
    {
        Section::query()->findOrFail($id)->delete();

        return back()->with('success', __('Section deleted successfully.'));
    }

    private function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'sectionName' => [
                'required',
                'string',
                'max:150',
                Rule::unique('lib_section', 'section_name')->ignore($ignoreId),
            ],
            'divisionId' => ['required', 'integer', Rule::exists('lib_division', 'id')],
            'secAcronym' => ['nullable', 'string', 'max:50'],
            'secStatus' => ['required', 'integer', 'in:0,1'],
        ]);

        return [
            'section_name' => $data['sectionName'],
            'division_id' => (int) $data['divisionId'],
            'sec_acronym' => filled($data['secAcronym'] ?? null) ? $data['secAcronym'] : null,
            'sec_status' => (int) $data['secStatus'],
        ];
    }
}
