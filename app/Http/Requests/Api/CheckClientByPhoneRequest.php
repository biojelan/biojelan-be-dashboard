<?php

namespace App\Http\Requests\Api;

class CheckClientByPhoneRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed check client phone!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'client_phone' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'client_phone.*' => 'Invalid client phone.',
        ];
    }
}
