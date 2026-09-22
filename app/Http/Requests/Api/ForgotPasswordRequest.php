<?php

namespace App\Http\Requests\Api;

class ForgotPasswordRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed send link reset password!';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.*' => 'Invalid email.',
        ];
    }
}
