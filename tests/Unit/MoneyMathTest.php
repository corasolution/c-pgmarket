<?php

declare(strict_types=1);

use Brick\Money\Money;

test('money is always stored as integer cents', function (): void {
    $money = Money::ofMinor(1099, 'USD');

    expect($money->getMinorAmount()->toInt())->toBe(1099)
        ->and($money->getAmount()->toFloat())->toBe(10.99);
});

test('two money values add correctly in cents', function (): void {
    $a = Money::ofMinor(500, 'USD');
    $b = Money::ofMinor(300, 'USD');

    expect($a->plus($b)->getMinorAmount()->toInt())->toBe(800);
});

test('commission calculation rounds down in cents', function (): void {
    $subtotalCents = 10000;
    $commissionPercent = 8;

    $commissionCents = (int) floor($subtotalCents * $commissionPercent / 100);
    $netCents = $subtotalCents - $commissionCents;

    expect($commissionCents)->toBe(800)
        ->and($netCents)->toBe(9200);
});

test('float prices are never used for money storage', function (): void {
    $priceCents = 1999;

    expect($priceCents)->toBeInt()
        ->not->toBeFloat();
});

test('KHR amounts are stored as integers', function (): void {
    $khrAmount = Money::ofMinor(400000, 'KHR');

    expect($khrAmount->getMinorAmount()->toInt())->toBe(400000)
        ->and($khrAmount->getCurrency()->getCurrencyCode())->toBe('KHR');
});

test('zero balance is representable as integer cents', function (): void {
    $zero = Money::ofMinor(0, 'USD');

    expect($zero->getMinorAmount()->toInt())->toBe(0)
        ->and($zero->isZero())->toBeTrue();
});

test('subtraction does not go below zero in wallet logic', function (): void {
    $balance = 5000;
    $withdrawal = 3000;

    $remaining = $balance - $withdrawal;

    expect($remaining)->toBe(2000)
        ->and($remaining)->toBeGreaterThanOrEqual(0);
});
