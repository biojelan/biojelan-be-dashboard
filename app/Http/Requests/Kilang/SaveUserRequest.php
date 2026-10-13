<?php

namespace App\Http\Requests\Kilang;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actor = $this->user();
        $target = $this->route('user');

        return $actor instanceof User && $actor->can('kilang-users')
            && (! $target instanceof User || $actor->role_id === User::SUPERADMIN_ROLE_ID || ! in_array($target->role_id, [1, 2], true));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $target = $this->route('user');
        $roles = [3, 4, 5, 6, 7];
        if ($this->user()?->role_id === User::SUPERADMIN_ROLE_ID) {
            $roles[] = 2;
        }
        if ($target instanceof User) {
            $roles[] = $target->role_id;
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($target instanceof User ? $target->id : null)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users')->ignore($target instanceof User ? $target->id : null)],
            'password' => [$target instanceof User ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', Rule::in($roles)],
            'is_active' => ['required', 'boolean'],
            'profile' => ['array'],
            'profile.address' => ['required_if:role_id,6', 'nullable', 'string', 'max:255'],
            'profile.latitude' => ['required_if:role_id,6', 'nullable', 'numeric', 'between:-90,90'],
            'profile.longitude' => ['required_if:role_id,6', 'nullable', 'numeric', 'between:-180,180'],
            'profile.bank_name' => ['required_if:role_id,6', 'nullable', 'string', 'max:255'],
            'profile.account_number' => ['required_if:role_id,6', 'nullable', 'string', 'max:255'],
            'profile.open_at' => ['required_if:role_id,6', 'nullable', 'date_format:H:i'],
            'profile.close_at' => ['required_if:role_id,6', 'nullable', 'date_format:H:i'],
            'profile.open_days' => ['required_if:role_id,6', 'array', 'min:1'],
            'profile.open_days.*' => ['string', 'distinct', Rule::in(['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu'])],
            'profile.plate_number' => ['required_if:role_id,5', 'nullable', 'string', 'max:255', Rule::unique('drivers', 'plate_number')->ignore($target instanceof User ? $target->id : null, 'driver_id')],
            'profile.type_vehicle' => ['required_if:role_id,5', 'nullable', 'string', 'max:255'],
        ];
    }
}
