<?php

declare(strict_types=1);

namespace App\Events\Shop;

use App\Models\Shop;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class ShopSuspended
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Shop $shop,
        public readonly string $reason,
    ) {}
}
