<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Proxy model used exclusively by the Vendor panel Customers resource.
 * Avoids Filament v4's guard that hides resources whose model == auth user model.
 */
final class ShopCustomer extends User
{
    protected $table = 'users';
}
