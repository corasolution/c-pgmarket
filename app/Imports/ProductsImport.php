<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

final class ProductsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow, WithValidation
{
    public function __construct(private readonly int $shopId) {}

    public function collection(Collection $rows): void
    {
        $groups = $rows->groupBy('name_en');

        foreach ($groups as $nameEn => $variantRows) {
            $first = $variantRows->first();

            $category = Category::whereRaw("name_i18n->>'en' = ?", [(string) $first['category']])->first();

            $status = in_array($first['status'] ?? '', ['active', 'archived'], strict: true)
                ? $first['status']
                : 'draft';

            $product = Product::withoutGlobalScopes()
                ->where('shop_id', $this->shopId)
                ->whereRaw("name_i18n->>'en' = ?", [(string) $nameEn])
                ->first();

            if (! $product) {
                $product = new Product();
                $product->shop_id          = $this->shopId;
                $product->slug             = Str::slug((string) $nameEn) . '-' . Str::lower(Str::random(5));
                $product->is_featured      = false;
            }

            $product->category_id      = $category?->id;
            $product->name_i18n        = ['en' => (string) $nameEn, 'km' => (string) ($first['name_km'] ?? '')];
            $product->description_i18n = ['en' => (string) ($first['description_en'] ?? ''), 'km' => ''];
            $product->status           = $status;
            $product->save();

            foreach ($variantRows as $row) {
                $sku = (string) ($row['sku'] ?? '');

                if (! $sku) {
                    continue;
                }

                if (ProductVariant::where('product_id', $product->id)->where('sku', $sku)->exists()) {
                    continue;
                }

                $options = [];
                $rawOptions = (string) ($row['options'] ?? '');

                if ($rawOptions !== '') {
                    foreach (explode('|', $rawOptions) as $pair) {
                        [$key, $val] = array_pad(explode(':', $pair, 2), 2, '');
                        $key = trim($key);
                        $val = trim($val);
                        if ($key !== '' && $val !== '') {
                            $options[$key] = $val;
                        }
                    }
                }

                ProductVariant::create([
                    'product_id'     => $product->id,
                    'sku'            => $sku,
                    'price_cents'    => (int) round((float) ($row['price'] ?? 0) * 100),
                    'price_currency' => strtoupper((string) ($row['currency'] ?? 'USD')),
                    'stock_quantity' => (int) ($row['stock'] ?? 0),
                    'options'        => $options,
                    'is_active'      => true,
                ]);
            }
        }
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'name_en'  => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'sku'      => 'required|string|max:100',
            'price'    => 'required|numeric|min:0',
            'stock'    => 'required|integer|min:0',
        ];
    }

    /** @return array<string, string> */
    public function customValidationMessages(): array
    {
        return [
            'name_en.required'  => 'Column name_en is required.',
            'category.required' => 'Column category is required.',
            'sku.required'      => 'Column sku is required.',
            'price.required'    => 'Column price is required.',
            'stock.required'    => 'Column stock is required.',
        ];
    }
}
