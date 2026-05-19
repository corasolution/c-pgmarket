<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

final class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'shipping_address'              => ['required', 'array'],
            'shipping_address.name'         => ['required', 'string', 'max:255'],
            'shipping_address.phone'        => ['required', 'string', 'max:30'],
            'shipping_address.address_line' => ['required', 'string', 'max:500'],
            'shipping_address.city'         => ['required', 'string', 'max:100'],
            'shipping_address.province'     => ['sometimes', 'string', 'max:100'],
            'note'                          => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'shipping_address.required'              => __('A shipping address is required.'),
            'shipping_address.name.required'         => __('Recipient name is required.'),
            'shipping_address.phone.required'        => __('Recipient phone number is required.'),
            'shipping_address.address_line.required' => __('Street address is required.'),
            'shipping_address.city.required'         => __('City is required.'),
        ];
    }
}
