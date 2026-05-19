<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class AddressController extends Controller
{
    public function index(Request $request): Response
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('storefront/addresses/index', [
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label'        => ['required', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:500'],
            'city'         => ['required', 'string', 'max:100'],
            'province'     => ['nullable', 'string', 'max:100'],
            'is_default'   => ['boolean'],
        ]);

        $user = $request->user();

        // If marking as default, unset other defaults
        if (! empty($validated['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        // Auto-set first address as default
        if ($user->addresses()->count() === 0) {
            $validated['is_default'] = true;
        }

        $user->addresses()->create($validated);

        return back()->with('success', __('Address added successfully.'));
    }

    public function update(Request $request, UserAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'label'        => ['required', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:255'],
            'phone'        => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:500'],
            'city'         => ['required', 'string', 'max:100'],
            'province'     => ['nullable', 'string', 'max:100'],
            'is_default'   => ['boolean'],
        ]);

        if (! empty($validated['is_default'])) {
            $request->user()->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update($validated);

        return back()->with('success', __('Address updated successfully.'));
    }

    public function destroy(Request $request, UserAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // If deleted address was default, promote the newest remaining
        if ($wasDefault) {
            $request->user()->addresses()->orderByDesc('updated_at')->first()?->update(['is_default' => true]);
        }

        return back()->with('success', __('Address removed.'));
    }

    public function setDefault(Request $request, UserAddress $address): RedirectResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403);
        }

        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('success', __('Default address updated.'));
    }
}
