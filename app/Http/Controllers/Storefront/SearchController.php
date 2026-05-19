<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = (string) $request->get('q', '');

        $products = $query !== ''
            ? Product::where('status', 'active')
                ->whereRaw("LOWER(name_i18n->>'en') LIKE LOWER(?)", ['%'.$query.'%'])
                ->with(['variants' => fn ($q) => $q->where('is_active', true)->orderBy('price_cents')])
                ->limit(48)
                ->get()
            : collect();

        return Inertia::render('storefront/search', [
            'query' => $query,
            'products' => $products,
        ]);
    }
}
