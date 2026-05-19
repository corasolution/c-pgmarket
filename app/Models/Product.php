<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToShop;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

final class Product extends Model
{
    use BelongsToShop, HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'shop_id', 'category_id', 'brand_id', 'name_i18n', 'description_i18n', 'slug',
        'images', 'status', 'is_featured', 'stock_track', 'attributes',
    ];

    protected function casts(): array
    {
        return [
            'name_i18n' => 'array',
            'description_i18n' => 'array',
            'attributes' => 'array',
            'is_featured' => 'boolean',
            'stock_track' => 'boolean',
        ];
    }

    /** Resolve image paths to full public storage URLs. */
    protected function images(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value): array {
                $paths = is_string($value) ? json_decode($value, true) : $value;
                return array_map(
                    fn (string $path) => str_starts_with($path, 'http://') || str_starts_with($path, 'https://')
                        ? $path
                        : Storage::disk('public')->url($path),
                    $paths ?? []
                );
            },
            set: fn (mixed $value) => is_array($value) ? json_encode($value) : $value,
        );
    }

    /** @return array<string, mixed> */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name_en' => $this->name_i18n['en'] ?? '',
            'name_km' => $this->name_i18n['km'] ?? '',
            'shop_id' => $this->shop_id,
            'category_id' => $this->category_id,
            'status' => $this->status,
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return HasMany<ProductVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /** @return HasMany<Favorite, $this> */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }
}
