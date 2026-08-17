<?php

namespace App\Models;

use App\Data\SidebarMenuNode;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $label
 * @property string|null $key
 * @property string|null $href
 * @property string|null $icon
 * @property string|null $badge_text
 * @property string|null $badge_cls
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable([
    'parent_id',
    'label',
    'key',
    'href',
    'icon',
    'badge_text',
    'badge_cls',
    'sort_order',
    'is_active',
])]
class SidebarMenuItem extends Model
{
    /** @var list<string> */
    public const BADGE_COLORS = [
        'red', 'orange', 'amber', 'yellow', 'lime', 'green', 'emerald', 'teal', 'cyan',
        'sky', 'blue', 'indigo', 'violet', 'purple', 'fuchsia', 'pink', 'rose',
    ];

    protected $table = 'sidebar_menu_items';

    protected $casts = [
        'is_active' => 'bool',
        'sort_order' => 'int',
        'parent_id' => 'int',
    ];

    /** @return BelongsTo<self, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<self, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw('case when parent_id is null then 0 else 1 end')
            ->orderBy('parent_id')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    /** @return Collection<int, int> */
    public function descendants(): Collection
    {
        $items = static::query()->ordered()->get(['id', 'parent_id']);
        $byParent = $items->groupBy('parent_id');
        $descendants = collect();

        $walk = function (int $parentId) use (&$walk, $byParent, $descendants): void {
            foreach ($byParent->get($parentId, collect()) as $child) {
                $descendants->push($child->id);
                $walk($child->id);
            }
        };

        $walk($this->id);

        return $descendants->values();
    }

    /**
     * @return list<SidebarMenuNode>
     */
    public static function tree(bool $onlyActive = true): array
    {
        if (! Schema::hasTable('sidebar_menu_items')) {
            return [];
        }

        $query = static::query();

        if ($onlyActive) {
            $query->active();
        }

        $items = $query->ordered()->get();

        $byParent = $items->groupBy('parent_id');

        $build = function (?int $parentId) use (&$build, $byParent): array {
            return array_values(($byParent->get($parentId) ?? collect())
                ->map(function (self $item) use (&$build): SidebarMenuNode {
                    return new SidebarMenuNode($item, $build($item->id));
                })
                ->all());
        };

        return $build(null);
    }

    /**
     * @return list<SidebarMenuNode>
     */
    public static function treeAll(): array
    {
        return static::tree(false);
    }
}
