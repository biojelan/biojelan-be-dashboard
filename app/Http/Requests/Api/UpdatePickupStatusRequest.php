<?php

namespace App\Http\Requests\Api;

use App\PickupStatus;
use Illuminate\Validation\Rule;

class UpdatePickupStatusRequest extends ApiRequest
{
    protected string $failureMessage = 'Failed update pickup status!';

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        return [
            'pickup_id' => ['required', 'string', 'regex:/^(?:pkp-)?[0-9]+$/'],
            'status' => [
                'required',
                Rule::in([
                    PickupStatus::Assigned->value,
                    PickupStatus::Otw->value,
                    PickupStatus::Arrived->value,
                    PickupStatus::Completed->value,
                    PickupStatus::Cancelled->value,
                ]),
            ],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'pickup_id.required' => 'Pickup id is required.',
            'pickup_id.regex' => 'Invalid pickup id.',
            'status.required' => 'Pickup status is required.',
            'status.in' => 'Invalid pickup status.',
        ];
    }
}
