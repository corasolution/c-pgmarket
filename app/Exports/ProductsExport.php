<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

final class ProductsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private readonly int $shopId) {}

    /** @return Collection<int, array{product: Product, variant: ProductVariant|null}> */
    public function collection(): Collection
    {
        $products = Product::withoutGlobalScopes()
            ->with(['variants', 'category'])
            ->where('shop_id', $this->shopId)
            ->get();

        return $products->flatMap(function (Product $product): Collection {
            if ($product->variants->isEmpty()) {
                return collect([['product' => $product, 'variant' => null]]);
            }

            return $product->variants->map(fn (ProductVariant $v) => ['product' => $product, 'variant' => $v]);
        });
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return ['name_en', 'name_km', 'category', 'status', 'description_en', 'sku', 'price', 'currency', 'stock', 'options'];
    }

    /** @param array{product: Product, variant: ProductVariant|null} $row */
    public function map($row): array
    {
        $product = $row['product'];
        $variant = $row['variant'];

        $options = $variant
            ? collect($variant->options ?? [])->map(fn ($v, $k) => "{$k}:{$v}")->implode('|')
            : '';

        return [
            $product->name_i18n['en'] ?? '',
            $product->name_i18n['km'] ?? '',
            $product->category?->name_i18n['en'] ?? '',
            $product->status,
            strip_tags($product->description_i18n['en'] ?? ''),
            $variant?->sku ?? '',
            $variant ? number_format($variant->price_cents / 100, 2, '.', '') : '',
            $variant?->price_currency ?? 'USD',
            $variant?->stock_quantity ?? 0,
            $options,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1D4ED8']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Products';
    }
}
