<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Dispute;
use App\Models\Review;
use App\Models\Shop;
use App\Models\SubOrder;
use Illuminate\Support\Facades\DB;

/**
 * Calculates seller performance metrics for a given shop.
 *
 * Metrics:
 *  - avg_rating: average review rating (1-5)
 *  - total_orders: total completed/delivered sub-orders
 *  - cancel_rate: % of cancelled sub-orders
 *  - dispute_rate: % of orders with disputes
 *  - avg_fulfillment_hours: average hours from accepted → packed
 *  - overall_score: weighted composite (0-100)
 */
final class SellerPerformanceService
{
    /**
     * @return array{avg_rating: float, total_orders: int, cancel_rate: float, dispute_rate: float, avg_fulfillment_hours: float|null, overall_score: int}
     */
    public function calculate(Shop $shop): array
    {
        $totalOrders = SubOrder::where('shop_id', $shop->id)
            ->whereNotIn('status', ['pending'])
            ->count();

        $completedOrders = SubOrder::where('shop_id', $shop->id)
            ->whereIn('status', ['delivered', 'completed'])
            ->count();

        $cancelledOrders = SubOrder::where('shop_id', $shop->id)
            ->where('status', 'cancelled')
            ->count();

        $disputedOrders = Dispute::where('shop_id', $shop->id)->count();

        $avgRating = (float) Review::where('shop_id', $shop->id)->avg('rating');

        // Average fulfillment time (accepted_at to packed — approximated by updated_at diffs)
        $avgFulfillmentHours = DB::table('sub_orders')
            ->where('shop_id', $shop->id)
            ->whereIn('status', ['packed', 'picked_up', 'in_transit', 'delivered', 'completed'])
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (updated_at - created_at)) / 3600) as avg_hours')
            ->value('avg_hours');

        $cancelRate = $totalOrders > 0 ? round($cancelledOrders / $totalOrders * 100, 1) : 0.0;
        $disputeRate = $totalOrders > 0 ? round($disputedOrders / $totalOrders * 100, 1) : 0.0;

        // Weighted overall score (0-100)
        $ratingScore = $avgRating > 0 ? ($avgRating / 5) * 40 : 20; // 40% weight
        $completionScore = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 30 : 15; // 30% weight
        $noCancelScore = (100 - $cancelRate) / 100 * 15; // 15% weight
        $noDisputeScore = (100 - $disputeRate) / 100 * 15; // 15% weight
        $overallScore = (int) round($ratingScore + $completionScore + $noCancelScore + $noDisputeScore);

        return [
            'avg_rating'            => round($avgRating, 1),
            'total_orders'          => $totalOrders,
            'cancel_rate'           => $cancelRate,
            'dispute_rate'          => $disputeRate,
            'avg_fulfillment_hours' => $avgFulfillmentHours !== null ? round((float) $avgFulfillmentHours, 1) : null,
            'overall_score'         => min(100, max(0, $overallScore)),
        ];
    }
}
