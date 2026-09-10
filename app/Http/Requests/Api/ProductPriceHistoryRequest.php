<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProductPriceHistoryRequest extends FormRequest
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
            'period' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    /**
     * Get the period in days, defaulting to 90.
     */
    public function period(): int
    {
        return (int) $this->query('period', 90);
    }
}
