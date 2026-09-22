<?php

namespace App\Http\Requests\Api;

class LoginRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed login!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.*' => 'Invalid email.',
            'password.*' => 'Invalid password.',
        ];
    }
}
