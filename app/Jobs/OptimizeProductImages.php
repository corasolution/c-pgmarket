<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Gd\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

final class OptimizeProductImages implements ShouldQueue
{
    use Queueable;

    /** @var array<string, array{size: int, quality: int}> */
    private const VARIANTS = [
        '_thumb' => ['size' => 150, 'quality' => 60],   // ~5-10 KB  — cards/listings
        '_md'    => ['size' => 450, 'quality' => 65],   // ~20-40 KB — product detail
        '_lg'    => ['size' => 900, 'quality' => 70],   // ~60-100 KB — lightbox/zoom
    ];

    private const ORIGINAL_MAX = 1200;
    private const ORIGINAL_QUALITY = 75;

    /**
     * @param array<int, string> $imagePaths Relative paths on the public disk
     * @param int|null $productId Product ID to update stored paths
     */
    public function __construct(
        private readonly array $imagePaths,
        private readonly ?int $productId = null,
    ) {}

    public function handle(): void
    {
        $manager = new ImageManager(new GdDriver());
        $disk = Storage::disk('public');
        /** @var array<string, string> $pathMap old path => new webp path */
        $pathMap = [];

        foreach ($this->imagePaths as $path) {
            $absolutePath = $disk->path($path);

            if (! file_exists($absolutePath)) {
                continue;
            }

            $dir = pathinfo($path, PATHINFO_DIRNAME);
            $name = pathinfo($path, PATHINFO_FILENAME);
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

            // Skip already-processed variant files
            if (preg_match('/_(thumb|md|lg)$/', $name)) {
                continue;
            }

            // Generate each variant
            foreach (self::VARIANTS as $suffix => $config) {
                $variantPath = $dir . '/' . $name . $suffix . '.webp';
                $variantAbsolute = $disk->path($variantPath);

                $image = $manager->decodePath($absolutePath);
                $image->scaleDown($config['size'], $config['size']);
                $image->encode(new WebpEncoder(quality: $config['quality']))
                    ->save($variantAbsolute);
            }

            // Compress original → WebP
            $webpPath = $dir . '/' . $name . '.webp';
            $webpAbsolute = $disk->path($webpPath);

            $image = $manager->decodePath($absolutePath);
            $image->scaleDown(self::ORIGINAL_MAX, self::ORIGINAL_MAX);
            $image->encode(new WebpEncoder(quality: self::ORIGINAL_QUALITY))
                ->save($webpAbsolute);

            // Delete old file if it was not already webp
            if ($ext !== 'webp' && $absolutePath !== $webpAbsolute) {
                @unlink($absolutePath);
                $pathMap[$path] = $webpPath;
            }
        }

        // Update stored paths in DB if any were converted
        if ($this->productId !== null && ! empty($pathMap)) {
            $product = Product::find($this->productId);

            if ($product !== null) {
                $raw = $product->getRawOriginal('images');
                $paths = is_string($raw) ? json_decode($raw, true) : [];

                $updated = array_map(
                    fn (string $p) => $pathMap[$p] ?? $p,
                    $paths ?? [],
                );

                $product->updateQuietly(['images' => json_encode($updated)]);
            }
        }
    }
}
