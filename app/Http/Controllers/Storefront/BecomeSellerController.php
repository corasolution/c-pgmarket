<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\Shop\CreateShop;
use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class BecomeSellerController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        // Already a vendor — redirect to vendor panel
        if (in_array($user->role, ['vendor_owner', 'vendor_staff'], true)) {
            return redirect('/vendor-panel');
        }

        return Inertia::render('storefront/become-seller');
    }

    public function store(Request $request, CreateShop $createShop): RedirectResponse
    {
        $user = $request->user();

        if ($user->role !== 'buyer') {
            return back()->withErrors(['role' => 'Only buyers can become sellers.']);
        }

        if (Shop::where('owner_id', $user->id)->exists()) {
            return back()->withErrors(['shop' => 'You already own a shop.']);
        }

        $validated = $request->validate([
            'shop_name'      => ['required', 'string', 'max:255', 'min:3'],
            'accept_terms'   => ['required', 'accepted'],
        ]);

        // Convert buyer to vendor_owner first (CreateShop requires this role)
        $user->update(['role' => 'vendor_owner']);

        try {
            $createShop($user, [
                'name' => $validated['shop_name'],
            ]);
        } catch (\Throwable $e) {
            // Rollback role if shop creation fails
            $user->update(['role' => 'buyer']);
            return back()->withErrors(['shop' => 'Failed to create shop. Please try again.']);
        }

        return redirect('/vendor-panel')->with('success', 'Welcome to PG Market! Your shop has been created.');
    }
}
