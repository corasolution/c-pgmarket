<?php

return [
    'commission_percent' => (int) env('PLATFORM_COMMISSION_PERCENT', 8),
    'escrow_days' => (int) env('PLATFORM_ESCROW_DAYS', 7),
    'currency' => env('PLATFORM_CURRENCY', 'USD'),
];
