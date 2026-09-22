<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property array{token: string, name: string, email: string} $resource */
class AuthenticationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{token: string, name: string, email: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->resource['token'],
            'name' => $this->resource['name'],
            'email' => $this->resource['email'],
        ];
    }
}
