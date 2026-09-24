<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;

class StoreClientTransactionRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed create transaction!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'client_email' => ['bail', 'required_without:client_phone', 'email', 'max:255'],
            'client_phone' => ['bail', 'required_without:client_email', 'string', 'max:255'],
            'volume_liter' => ['required', 'decimal:0,3', 'gt:0'],
            'transaction_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'client_email.required_without' => 'Client email or phone is required.',
            'client_email.email' => 'Invalid client email.',
            'client_phone.required_without' => 'Client email or phone is required.',
            'client_phone.string' => 'Invalid client phone.',
            'volume_liter.*' => 'Invalid volume liter.',
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('client_email') && $this->filled('client_phone')) {
                $validator->errors()->add('client_email', 'Choose either client email or phone.');
            }
        }];
    }
}
