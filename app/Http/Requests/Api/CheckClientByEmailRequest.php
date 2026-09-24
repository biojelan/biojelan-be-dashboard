<?php

namespace App\Http\Requests\Api;

class CheckClientByEmailRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed check client email!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'client_email' => ['required', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'client_email.*' => 'Invalid client email.',
        ];
    }
}
