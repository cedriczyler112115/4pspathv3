<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final class UserDirectory
{
    /** @return LengthAwarePaginator<int, stdClass> */
    public function search(string $search, string $divisionId, string $sectionId, int $perPage): LengthAwarePaginator
    {
        return $this->baseQuery([
            'users.id',
            'users.last_name',
            'users.first_name',
            'users.middle_name',
            'users.extension_name',
            'users.email',
            'users.contact_number',
            'users.position',
        ])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($nameQuery) use ($like): void {
                    $nameQuery
                        ->whereRaw("CONCAT_WS(' ', users.last_name, users.first_name, users.middle_name, users.extension_name) LIKE ?", [$like])
                        ->orWhereRaw("CONCAT_WS(' ', users.first_name, users.middle_name, users.last_name, users.extension_name) LIKE ?", [$like]);
                });
            })
            ->when($divisionId !== '', fn ($query) => $query->where('users.division_id', $divisionId))
            ->when($sectionId !== '', fn ($query) => $query->where('users.section_id', $sectionId))
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->paginate($perPage);
    }

    /** @return LengthAwarePaginator<int, stdClass> */
    public function administration(string $search, string $divisionId, string $sectionId, string $status, int $perPage, string $userLevelId = ''): LengthAwarePaginator
    {
        return $this->baseQuery([
            'users.id',
            'users.last_name',
            'users.first_name',
            'users.middle_name',
            'users.extension_name',
            'users.email',
            'users.contact_number',
            'users.position',
            'users.designation',
            'users.division_id',
            'users.section_id',
            'users.supervisor_id',
            'users.is_supervisor',
            'users.division',
            'users.section',
            'users.is_status',
            'users.user_level_id',
            'user_level.level_name as user_level_name',
        ])
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($searchQuery) use ($like): void {
                    $searchQuery
                        ->whereRaw("CONCAT_WS(' ', users.last_name, users.first_name, users.middle_name, users.extension_name) LIKE ?", [$like])
                        ->orWhere('users.position', 'like', $like)
                        ->orWhere('users.designation', 'like', $like);
                });
            })
            ->when($divisionId !== '', fn ($query) => $query->where('users.division_id', $divisionId))
            ->when($sectionId !== '', fn ($query) => $query->where('users.section_id', $sectionId))
            ->when($userLevelId !== '', fn ($query) => $query->where('users.user_level_id', $userLevelId))
            ->when($status !== '', fn ($query) => $query->where('users.is_status', $status))
            ->orderBy('users.id')
            ->paginate($perPage);
    }

    /** @return LengthAwarePaginator<int, stdClass> */
    public function supervisedStaff(int $supervisorId, string $search, string $divisionId, string $sectionId, string $status, int $perPage): LengthAwarePaginator
    {
        return $this->baseQuery([
            'users.id',
            'users.last_name',
            'users.first_name',
            'users.middle_name',
            'users.extension_name',
            'users.email',
            'users.contact_number',
            'users.position',
            'users.designation',
            'users.division_id',
            'users.section_id',
            'users.supervisor_id',
            'users.is_supervisor',
            'users.division',
            'users.section',
            'users.is_status',
            'users.user_level_id',
            'user_level.level_name as user_level_name',
        ])
            ->where('users.supervisor_id', $supervisorId)
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';

                $query->where(function ($searchQuery) use ($like): void {
                    $searchQuery
                        ->whereRaw("CONCAT_WS(' ', users.last_name, users.first_name, users.middle_name, users.extension_name) LIKE ?", [$like])
                        ->orWhere('users.position', 'like', $like)
                        ->orWhere('users.designation', 'like', $like)
                        ->orWhere('users.email', 'like', $like);
                });
            })
            ->when($divisionId !== '', fn ($query) => $query->where('users.division_id', $divisionId))
            ->when($sectionId !== '', fn ($query) => $query->where('users.section_id', $sectionId))
            ->when($status !== '', fn ($query) => $query->where('users.is_status', $status))
            ->orderBy('users.last_name')
            ->orderBy('users.first_name')
            ->paginate($perPage);
    }

    /** @return Collection<int, stdClass> */
    public function divisions(): Collection
    {
        return DB::table('lib_division')->orderBy('division_name')->get(['id', 'division_name']);
    }

    /** @return Collection<int, stdClass> */
    public function sections(string $divisionId, bool $includeDivisionId = false): Collection
    {
        $columns = $includeDivisionId ? ['id', 'section_name', 'division_id'] : ['id', 'section_name'];

        return DB::table('lib_section')
            ->when($divisionId !== '', fn ($query) => $query->where('division_id', $divisionId))
            ->orderBy('section_name')
            ->get($columns);
    }

    /** @return Collection<int, stdClass> */
    public function supervisors(): Collection
    {
        return DB::table('users')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'last_name', 'first_name', 'middle_name', 'extension_name']);
    }

    /** @return Collection<int, stdClass> */
    public function userLevels(): Collection
    {
        return DB::table('user_level')
            ->where('is_status', 1)
            ->orderBy('level_name')
            ->get(['level_id', 'level_name']);
    }

    /** @param list<string> $columns */
    private function baseQuery(array $columns): Builder
    {
        return DB::table('users')
            ->leftJoin('lib_division', 'users.division_id', '=', 'lib_division.id')
            ->leftJoin('lib_section', 'users.section_id', '=', 'lib_section.id')
            ->leftJoin('user_level', 'users.user_level_id', '=', 'user_level.level_id')
            ->select([
                ...$columns,
                DB::raw('COALESCE(lib_division.division_name, users.division) as division_name'),
                DB::raw('COALESCE(lib_section.section_name, users.section) as section_name'),
            ]);
    }
}
