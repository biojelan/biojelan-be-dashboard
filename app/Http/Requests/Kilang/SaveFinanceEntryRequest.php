<?php

namespace App\Http\Requests\Kilang;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFinanceEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kilang-finance') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['SALE', 'EXPENSE'])],
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:9999999999999', 'decimal:0,2'],
            'volume_liter' => ['required_if:type,SALE', 'nullable', 'numeric', 'min:0.001', 'max:999999999', 'decimal:0,3'],
            'description' => ['required', 'string', 'max:1000'],
        ];
    }
}
