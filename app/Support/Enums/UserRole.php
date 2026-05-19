<?php

declare(strict_types=1);

namespace App\Support\Enums;

enum UserRole: string
{
    case Buyer = 'buyer';
    case VendorOwner = 'vendor_owner';
    case VendorStaff = 'vendor_staff';
    case Admin = 'admin';

    public function label(): string
    {
        return match($this) {
            self::Buyer       => 'Buyer',
            self::VendorOwner => 'Vendor Owner',
            self::VendorStaff => 'Vendor Staff',
            self::Admin       => 'Admin',
        };
    }

    public function requiresTwoFactor(): bool
    {
        return match($this) {
            self::Admin, self::VendorOwner => true,
            default                        => false,
        };
    }
}
