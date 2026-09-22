<?php

namespace App\Http\Requests\Api;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed update user!';

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()?->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['prohibited'],
            'agen' => ['sometimes', 'array'],
            'agen.address' => ['sometimes', 'string', 'max:255'],
            'agen.latitude' => ['sometimes', 'numeric'],
            'agen.longitude' => ['sometimes', 'numeric'],
            'agen.bank_name' => ['sometimes', 'string', 'max:255'],
            'agen.account_number' => ['sometimes', 'string', 'max:255'],
            'agen.open_at' => ['sometimes', 'date_format:H:i'],
            'agen.close_at' => ['sometimes', 'date_format:H:i'],
            'agen.open_days' => ['sometimes', 'array', 'min:1'],
            'agen.open_days.*' => ['string', 'max:20', 'distinct'],
            'agen.is_open' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email already exists.',
            'password.prohibited' => 'The password field is prohibited.',
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->has('agen')) {
                return;
            }

            /** @var User|null $user */
            $user = $this->user();

            if ($user?->role_id !== User::AGEN_ROLE_ID) {
                $validator->errors()->add('agen', 'Agen profile can only be updated by an agen user.');

                return;
            }

            if ($user->agen()->exists()) {
                return;
            }

            foreach (['address', 'latitude', 'longitude', 'bank_name', 'account_number', 'open_at', 'close_at', 'open_days'] as $field) {
                if (! array_key_exists($field, (array) $this->input('agen'))) {
                    $validator->errors()->add('agen.'.$field, 'The '.$field.' field is required when creating an agen profile.');
                }
            }
        }];
    }
}
