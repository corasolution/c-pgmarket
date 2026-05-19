<?php

declare(strict_types=1);

namespace App\Actions\Shop;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

final class SubmitShopForReview
{
    public function __invoke(Shop $shop, User $owner): void
    {
        if ($shop->owner_id !== $owner->id) {
            throw new AuthorizationException('Only the shop owner may submit for review.');
        }

        if ($shop->status !== 'draft') {
            throw new \RuntimeException("Shop must be in 'draft' status to submit for review. Current: {$shop->status}");
        }

        $shop->update(['status' => 'submitted']);
    }
}
