<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Category extends Model
{
    use HasFactory;
    protected $fillable = ['parent_id', 'name_i18n', 'slug', 'image', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['name_i18n' => 'array', 'is_active' => 'boolean'];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * BFS: returns IDs of this category and all its active descendants.
     *
     * @return list<int>
     */
    public function allDescendantIds(): array
    {
        $ids   = [$this->id];
        $queue = [$this->id];

        while (!empty($queue)) {
            $children = static::whereIn('parent_id', $queue)
                ->where('is_active', true)
                ->pluck('id')
                ->all();

            $ids   = array_merge($ids, $children);
            $queue = $children;
        }

        return $ids;
    }

    /**
     * Returns the ancestor chain from root down to the immediate parent.
     *
     * @return list<self>
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $current   = $this;

        while ($current->parent_id !== null) {
            $parent = static::find($current->parent_id, ['id', 'name_i18n', 'slug', 'parent_id']);
            if ($parent === null) {
                break;
            }
            array_unshift($ancestors, $parent);
            $current = $parent;
        }

        return $ancestors;
    }
}
