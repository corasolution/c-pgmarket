<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Laravel\Scout\Builder;

final class MeilisearchService
{
    /**
     * Full-text product search supporting both Khmer and English.
     *
     * Scout + Meilisearch handles the index; this service applies
     * business-level filters on top (active only, optional category).
     *
     * @return Collection<int, Product>
     */
    public function searchProducts(
        string $query,
        ?int $categoryId = null,
        int $perPage = 20,
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
    ): Collection {
        /** @var Builder $builder */
        $builder = Product::search($query)
            ->where('status', 'active');

        if ($categoryId !== null) {
            $builder->where('category_id', $categoryId);
        }

        $builder->orderBy($sortBy, $sortDir)->take($perPage);

        /** @var Collection<int, Product> $results */
        $results = $builder->get();

        return $results;
    }

    /**
     * Configure Meilisearch index settings for a model.
     * Call once via an Artisan command or seeder after `scout:import`.
     *
     * @return array<string, mixed>
     */
    public function getProductIndexSettings(): array
    {
        return [
            'searchableAttributes' => [
                'name_i18n.en',
                'name_i18n.km',
                'description_i18n.en',
                'description_i18n.km',
                'variants.sku',
            ],
            'filterableAttributes' => [
                'status',
                'category_id',
                'shop_id',
                'is_featured',
            ],
            'sortableAttributes' => [
                'created_at',
                'price_cents_min',
            ],
            'rankingRules' => [
                'words',
                'typo',
                'proximity',
                'attribute',
                'sort',
                'exactness',
            ],
        ];
    }
}
