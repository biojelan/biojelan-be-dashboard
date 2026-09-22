<?php

namespace App\Http\Requests\Api;

class UpdatePasswordRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed update password!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'max:255'],
            'new_password' => ['required', 'string', 'min:8', 'max:255'],
            'password_confirmation' => ['required', 'string', 'same:new_password'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'password.*' => 'Wrong password.',
            'new_password.*' => 'Invalid password.',
            'password_confirmation.*' => 'Invalid password confirmation.',
        ];
    }
}
