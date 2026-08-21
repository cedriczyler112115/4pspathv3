<?php

namespace App\Services;

use App\Models\UserLevel;
use Illuminate\Pagination\LengthAwarePaginator;

final class UserLevelDirectory
{
    /** @return LengthAwarePaginator<int, UserLevel> */
    public function paginate(string $search, int $perPage): LengthAwarePaginator
    {
        return UserLevel::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('level_name', 'like', '%'.$search.'%')
                        ->orWhere('level_id', 'like', '%'.$search.'%');
                });
            })
            ->orderBy('level_name')
            ->paginate($perPage);
    }
}
