<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use App\Models\VendorWallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ImportPgMarketShopsSeeder extends Seeder
{
    public function run(): void
    {
        $json = file_get_contents(base_path('storage/app/pgmarket_shops.json'));
        $shops = json_decode($json, true);

        $logoDir = storage_path('app/public/imported-shops/logos');
        $bannerDir = storage_path('app/public/imported-shops/banners');

        foreach ($shops as $s) {
            if ($s['status'] !== 'active') {
                continue;
            }

            // Create or find vendor user
            $email = Str::slug($s['name'], '.') . '@pgmarket.online';
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $s['name'] . ' Owner',
                    'password' => Hash::make('password'),
                    'role' => 'vendor_owner',
                    'phone' => $s['phone'] ?? null,
                    'email_verified_at' => now(),
                ],
            );

            // Copy logo to shop-logos directory
            $logoPath = null;
            if (!empty($s['logo'])) {
                $logoFile = $this->findFile($logoDir, (string) $s['id']);
                if ($logoFile) {
                    $destName = 'shop-logos/' . Str::slug($s['name']) . '-logo.' . pathinfo($logoFile, PATHINFO_EXTENSION);
                    $destPath = storage_path('app/public/' . $destName);
                    File::ensureDirectoryExists(dirname($destPath));
                    File::copy($logoFile, $destPath);
                    $logoPath = $destName;
                }
            }

            // Copy banner to shop-banners directory
            $bannerPath = null;
            if (!empty($s['banner'])) {
                $bannerFile = $this->findFile($bannerDir, (string) $s['id']);
                if ($bannerFile) {
                    $destName = 'shop-banners/' . Str::slug($s['name']) . '-banner.' . pathinfo($bannerFile, PATHINFO_EXTENSION);
                    $destPath = storage_path('app/public/' . $destName);
                    File::ensureDirectoryExists(dirname($destPath));
                    File::copy($bannerFile, $destPath);
                    $bannerPath = $destName;
                }
            }

            $shop = Shop::firstOrCreate(
                ['slug' => Str::slug($s['name'])],
                [
                    'owner_id' => $user->id,
                    'name' => $s['name'],
                    'slug' => Str::slug($s['name']),
                    'phone' => $s['phone'] ?? null,
                    'email' => $email,
                    'status' => 'active',
                    'commission_percent' => 8,
                    'logo' => $logoPath,
                    'banner' => $bannerPath,
                    'description_i18n' => [
                        'en' => $s['short_description'] ?? '',
                        'km' => $s['short_description_kh'] ?? '',
                    ],
                ],
            );

            // Ensure wallet exists
            VendorWallet::firstOrCreate(
                ['shop_id' => $shop->id],
                [
                    'pending_balance_cents' => 0,
                    'pending_balance_currency' => 'USD',
                    'available_balance_cents' => 0,
                    'available_balance_currency' => 'USD',
                    'lifetime_earned_cents' => 0,
                ],
            );

            $this->command->info("Imported: {$s['name']} → {$email}");
        }

        $this->command->info('PG Market shops import complete.');
    }

    private function findFile(string $dir, string $id): ?string
    {
        if (!is_dir($dir)) {
            return null;
        }

        $files = glob($dir . '/' . $id . '_*');

        return !empty($files) ? $files[0] : null;
    }
}
