<?php

namespace App\Http\Requests\Kilang;

use Illuminate\Foundation\Http\FormRequest;

class SchedulePickupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('kilang-operations') ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time' => ['required', 'date_format:H:i'],
            'stops' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'stops.*.agent_id' => ['required', 'integer', 'distinct', 'exists:users,id'],
            'stops.*.volume_liter' => ['required', 'numeric', 'min:0.001', 'max:999999999', 'decimal:0,3'],
        ];
    }

    /** @return array{driver_id: int, date: string, time: string, stops: list<array{agent_id: int, volume_liter: float}>} */
    public function pickupData(): array
    {
        $stops = [];
        foreach ($this->validated('stops') as $stop) {
            $stops[] = ['agent_id' => (int) $stop['agent_id'], 'volume_liter' => (float) $stop['volume_liter']];
        }

        return ['driver_id' => $this->integer('driver_id'), 'date' => $this->string('date')->toString(), 'time' => $this->string('time')->toString(), 'stops' => $stops];
    }
}
