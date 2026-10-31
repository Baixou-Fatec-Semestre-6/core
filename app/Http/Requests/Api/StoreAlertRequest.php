<?php

namespace App\Http\Requests\Api;

use App\Enums\NotificationChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlertRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_id' => [
                'required',
                Rule::exists('product_monitors', 'product_id')->where('user_id', $this->user()->id)
            ],
            'target_price' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'notification_channel' => ['sometimes', Rule::enum(NotificationChannel::class)],

        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'product_id.exists' => 'Você precisa monitorar este produto para criar um alerta.',
        ];
    }
}
