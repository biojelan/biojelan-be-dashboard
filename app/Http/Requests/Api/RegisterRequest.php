<?php

namespace App\Http\Requests\Api;

class RegisterRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed create user!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'name.*' => 'Invalid name.',
            'email.unique' => 'Email already exists.',
            'email.*' => 'Invalid email.',
            'password.*' => 'Invalid password.',
        ];
    }
}
