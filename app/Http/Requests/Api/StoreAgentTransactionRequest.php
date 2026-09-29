<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;

class StoreAgentTransactionRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed create transaction!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'agent_email' => ['bail', 'required_without:agent_phone', 'email', 'max:255'],
            'agent_phone' => ['bail', 'required_without:agent_email', 'string', 'max:255'],
            'volume_liter' => ['required', 'decimal:0,3', 'gt:0'],
            'transaction_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'agent_email.required_without' => 'Agent email or phone is required.',
            'agent_email.email' => 'Invalid agent email.',
            'agent_phone.required_without' => 'Agent email or phone is required.',
            'agent_phone.string' => 'Invalid agent phone.',
            'volume_liter.*' => 'Invalid volume liter.',
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('agent_email') && $this->filled('agent_phone')) {
                $validator->errors()->add('agent_email', 'Choose either agent email or phone.');
            }
        }];
    }
}
