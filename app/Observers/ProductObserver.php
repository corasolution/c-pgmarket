<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\OptimizeProductImages;
use App\Models\Product;

final class ProductObserver
{
    public function saved(Product $product): void
    {
        if (! $product->wasChanged('images')) {
            return;
        }

        $raw   = $product->getRawOriginal('images');
        $paths = is_string($raw) ? json_decode($raw, true) : [];

        if (! empty($paths)) {
            OptimizeProductImages::dispatch($paths, $product->id);
        }
    }
}
