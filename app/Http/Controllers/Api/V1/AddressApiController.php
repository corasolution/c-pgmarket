<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AddressApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['data' => $addresses]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();

        // Auto-set first address as default
        $hasAddresses = $user->addresses()->exists();
        $isDefault = ! $hasAddresses || ($validated['is_default'] ?? false);

        if ($isDefault && $hasAddresses) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = $user->addresses()->create([
            ...$validated,
            'is_default' => $isDefault,
        ]);

        return response()->json(['data' => $address], 201);
    }

    public function update(Request $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'This address does not belong to you.');
        }

        $validated = $request->validate([
            'label' => ['sometimes', 'string', 'max:50'],
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'address_line' => ['sometimes', 'string', 'max:500'],
            'city' => ['sometimes', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'is_default' => ['sometimes', 'boolean'],
        ]);

        if (($validated['is_default'] ?? false) === true) {
            $request->user()->addresses()
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return response()->json(['data' => $address->fresh()]);
    }

    public function destroy(Request $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'This address does not belong to you.');
        }

        $wasDefault = $address->is_default;

        $address->delete();

        // Promote next address to default if deleted was default
        if ($wasDefault) {
            $nextAddress = $request->user()->addresses()
                ->orderByDesc('updated_at')
                ->first();

            $nextAddress?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'Address deleted.']);
    }

    public function setDefault(Request $request, UserAddress $address): JsonResponse
    {
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'This address does not belong to you.');
        }

        $request->user()->addresses()->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return response()->json(['data' => $address->fresh()]);
    }
}
