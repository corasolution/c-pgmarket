<?php

declare(strict_types=1);

namespace App\Http\Controllers\Vendor;

use App\Exports\ProductImportTemplate;
use App\Exports\ProductsExport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProductExportController extends Controller
{
    public function export(): BinaryFileResponse
    {
        $shopId = $this->resolveShopId();

        return Excel::download(
            new ProductsExport($shopId),
            'products-' . now()->format('Y-m-d') . '.xlsx',
        );
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(
            new ProductImportTemplate(),
            'product-import-template.xlsx',
        );
    }

    private function resolveShopId(): int
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return $user->ownedShop?->id ?? $user->staffShop?->id ?? 0;
    }
}
