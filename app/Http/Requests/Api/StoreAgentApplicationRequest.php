<?php

namespace App\Http\Requests\Api;

use Illuminate\Validation\Rule;

class StoreAgentApplicationRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed submit agent request!';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'profile' => ['required', 'array'], 'profile.address' => ['required', 'string', 'max:255'],
            'profile.latitude' => ['required', 'numeric', 'between:-90,90'], 'profile.longitude' => ['required', 'numeric', 'between:-180,180'],
            'profile.bank_name' => ['required', 'string', 'max:255'], 'profile.account_number' => ['required', 'string', 'max:255'],
            'profile.open_at' => ['required', 'date_format:H:i'], 'profile.close_at' => ['required', 'date_format:H:i'],
            'profile.open_days' => ['required', 'array', 'min:1'],
            'profile.open_days.*' => ['required', 'string', 'distinct', Rule::in(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'])],
        ];
    }
}
