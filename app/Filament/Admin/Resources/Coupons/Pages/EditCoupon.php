<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Coupons\Pages;

use App\Filament\Admin\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\EditRecord;

final class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;
}
