<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Contracts\DeliveryProvider;
use App\Models\Shipment;
use App\Models\SubOrder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class ApolloDeliveryProvider implements DeliveryProvider
{
    private function baseUrl(): string
    {
        return rtrim((string) env('APOLLO_BASE_URL', 'https://apolo-api.codingate.com'), '/');
    }

    private function deviceOs(): string
    {
        return (string) env('APOLLO_DEVICE_OS', 'Web');
    }

    private function deviceId(): string
    {
        return (string) env('APOLLO_DEVICE_ID', 'pgmarket-platform-001');
    }

    private function email(): string
    {
        return (string) env('APOLLO_EMAIL', '');
    }

    private function password(): string
    {
        return (string) env('APOLLO_PASSWORD', '');
    }

    private function defaultServiceType(): string
    {
        return (string) env('APOLLO_DEFAULT_SERVICE_TYPE', 'next_day');
    }

    /**
     * Authenticate and return cached tokens.
     * Both token and access_token are cached until their respective expiry times.
     *
     * @return array{token: string, access_token: string}
     */
    private function authenticate(): array
    {
        $token = Cache::remember('apollo_device_token', 3600, function (): string {
            $response = Http::timeout(15)->post($this->baseUrl() . '/api/integration/pgmarket/authorize', [
                'device_os' => $this->deviceOs(),
                'device_id' => $this->deviceId(),
            ]);

            if (! $response->successful() || ! $response->json('success')) {
                throw new \RuntimeException('Apollo authorize failed: ' . $response->body());
            }

            $data = $response->json('data');
            $ttl  = max(60, (int) $data['expired_date'] - time() - 60);

            Cache::put('apollo_device_token', $data['token'], $ttl);

            return $data['token'];
        });

        $accessToken = Cache::remember('apollo_access_token', 3600, function () use ($token): string {
            $response = Http::timeout(15)
                ->withHeaders(['Authorize' => $token])
                ->post($this->baseUrl() . '/api/integration/pgmarket/login', [
                    'email'    => $this->email(),
                    'password' => $this->password(),
                ]);

            if (! $response->successful() || ! $response->json('success')) {
                throw new \RuntimeException('Apollo login failed: ' . $response->body());
            }

            $data = $response->json('data');
            $ttl  = max(60, (int) $data['expired_date'] - time() - 60);

            Cache::put('apollo_access_token', $data['access_token'], $ttl);

            return $data['access_token'];
        });

        return ['token' => $token, 'access_token' => $accessToken];
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        $tokens = $this->authenticate();

        return [
            'Authorize' => $tokens['token'],
            'Auth'      => $tokens['access_token'],
        ];
    }

    public function createShipment(SubOrder $subOrder): Shipment
    {
        $subOrder->loadMissing(['order', 'shop']);

        $order = $subOrder->order;
        $shop  = $subOrder->shop;

        // Get delivery fee estimate first
        $rate        = $this->getRate($subOrder);
        $deliveryFee = $rate['fee_usd'] ?? 0.0;

        $shippingAddress = $order->shipping_address ?? [];

        $payload = [
            'book_datetime'      => now()->format('Y-m-d H:i:s'),
            'sender_name'        => $shop->name,
            'sender_phone'       => $shop->phone ?? '',
            'sender_address'     => is_array($shop->address) ? implode(', ', array_filter($shop->address)) : (string) $shop->address,
            'sender_province_id' => $shop->apollo_province_id,
            'sender_district_id' => $shop->apollo_district_id,
            'delivery_fee'       => $deliveryFee,
            'service_type'       => $this->defaultServiceType(),
            'parcel'             => [
                'client_reference'  => $order->reference,
                'fee_payer'         => 'sender',
                'images'            => [],
                'parcel_weight'     => '500',
                'receiver_name'     => $shippingAddress['name'] ?? '',
                'receiver_phone'    => $shippingAddress['phone'] ?? '',
                'receiver_address'  => $shippingAddress['address_line'] ?? '',
                'receiver_province_id' => $order->apollo_receiver_province_id,
                'note'              => $subOrder->vendor_note ?? '',
            ],
        ];

        $response = Http::timeout(30)
            ->withHeaders($this->authHeaders())
            ->post($this->baseUrl() . '/api/integration/pgmarket/booking/store', $payload);

        if (! $response->successful() || ! $response->json('success')) {
            throw new \RuntimeException('Apollo booking failed: ' . $response->body());
        }

        $parcelCode = (string) $response->json('data');

        $feeCents = (int) round($deliveryFee * 100);

        $shipment = Shipment::create([
            'sub_order_id'          => $subOrder->id,
            'provider'              => 'apollo',
            'tracking_number'       => $parcelCode,
            'status'                => 'pending',
            'shipping_fee_cents'    => $feeCents,
            'shipping_fee_currency' => 'USD',
            'provider_response'     => $response->json(),
        ]);

        // Update the sub-order shipping fee
        $subOrder->update(['shipping_fee_cents' => $feeCents]);

        return $shipment;
    }

    /** @return array<string, mixed> */
    public function getRate(SubOrder $subOrder): array
    {
        $subOrder->loadMissing(['order', 'shop']);

        $senderProvinceId   = $subOrder->shop->apollo_province_id;
        $receiverProvinceId = $subOrder->order->apollo_receiver_province_id;

        if (! $senderProvinceId || ! $receiverProvinceId) {
            return ['fee_cents' => 0, 'fee_usd' => 0.0, 'currency' => 'USD'];
        }

        $response = Http::timeout(15)
            ->withHeaders($this->authHeaders())
            ->get($this->baseUrl() . '/api/integration/pgmarket/booking/delivery-fee', [
                'sender_province_id'   => $senderProvinceId,
                'receiver_province_id' => $receiverProvinceId,
                'service_type'         => $this->defaultServiceType(),
                'weight'               => 500,
            ]);

        if (! $response->successful() || ! $response->json('success')) {
            Log::warning('Apollo getRate failed', ['body' => $response->body()]);

            return ['fee_cents' => 0, 'fee_usd' => 0.0, 'currency' => 'USD'];
        }

        $data   = $response->json('data');
        $feeUsd = (float) ($data['delivery_fee_usd'] ?? 0);

        return [
            'fee_cents' => (int) round($feeUsd * 100),
            'fee_usd'   => $feeUsd,
            'currency'  => 'USD',
        ];
    }

    /** @return array<string, mixed> */
    public function trackShipment(Shipment $shipment): array
    {
        if (! $shipment->tracking_number) {
            return ['status' => $shipment->status, 'events' => []];
        }

        $response = Http::timeout(15)
            ->withHeaders($this->authHeaders())
            ->get($this->baseUrl() . '/api/integration/pgmarket/booking/detail/' . $shipment->tracking_number);

        if (! $response->successful() || ! $response->json('success')) {
            Log::warning('Apollo trackShipment failed', [
                'tracking' => $shipment->tracking_number,
                'body'     => $response->body(),
            ]);

            return ['status' => $shipment->status, 'events' => []];
        }

        $data = $response->json('data');

        return [
            'status'           => $data['parcel_status_title'] ?? $shipment->status,
            'events'           => $data['trackings'] ?? [],
            'receiver_address' => $data['receiver_address'] ?? null,
            'sender_address'   => $data['sender_address'] ?? null,
        ];
    }

    public function cancelShipment(Shipment $shipment): bool
    {
        // Apollo API does not expose a cancel endpoint in the integration doc.
        Log::warning('Apollo cancelShipment not supported', ['tracking' => $shipment->tracking_number]);

        return false;
    }

    /** @param array<string, mixed> $payload */
    public function handleWebhook(array $payload): void
    {
        // Apollo has not documented a webhook endpoint in the integration spec.
        // Implement when Apollo provides webhook support.
        Log::info('Apollo webhook received', $payload);
    }

    /**
     * Fetch all provinces from Apollo (cached for 24h).
     * Used by Shop Settings province selector.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getProvinces(): array
    {
        return Cache::remember('apollo_provinces', 86400, function (): array {
            $response = Http::timeout(15)
                ->withHeaders($this->authHeaders())
                ->get($this->baseUrl() . '/api/integration/pgmarket/provinces');

            if (! $response->successful() || ! $response->json('success')) {
                return [];
            }

            return $response->json('data') ?? [];
        });
    }

    /**
     * Fetch districts for a province (cached for 24h).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDistricts(int $provinceId): array
    {
        return Cache::remember("apollo_districts_{$provinceId}", 86400, function () use ($provinceId): array {
            $response = Http::timeout(15)
                ->withHeaders($this->authHeaders())
                ->get($this->baseUrl() . '/api/integration/pgmarket/districts', [
                    'province_id' => $provinceId,
                ]);

            if (! $response->successful() || ! $response->json('success')) {
                return [];
            }

            return $response->json('data') ?? [];
        });
    }

    /**
     * Map a province name string to its Apollo numeric ID.
     * Used during checkout to store receiver_province_id on the Order.
     */
    public function findProvinceIdByName(string $name): ?int
    {
        $provinces = $this->getProvinces();
        $name      = strtolower(trim($name));

        foreach ($provinces as $province) {
            if (strtolower((string) $province['name']) === $name) {
                return (int) $province['id'];
            }
        }

        return null;
    }
}
