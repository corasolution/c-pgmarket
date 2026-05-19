<?php

declare(strict_types=1);

namespace App\Http\Requests\Storefront;

use Illuminate\Foundation\Http\FormRequest;

final class StoreConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'buyer';
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'body'    => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'shop_id.required' => __('A shop must be selected.'),
            'shop_id.exists'   => __('The selected shop does not exist.'),
            'body.required'    => __('Please enter a message to start the conversation.'),
            'body.max'         => __('Your message cannot exceed 2000 characters.'),
        ];
    }
}
