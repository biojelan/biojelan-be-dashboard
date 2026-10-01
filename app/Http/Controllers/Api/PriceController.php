<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PriceResource;
use App\Models\Price;
use App\PriceType;
use Illuminate\Http\JsonResponse;

class PriceController extends Controller
{
    public function index(): JsonResponse
    {
        $price = Price::query()
            ->where('price_type', PriceType::Client)
            ->whereDate('start_date', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })
            ->latest('start_date')
            ->first();

        if ($price === null) {
            return response()->json([
                'data' => (object) [],
                'message' => 'Failed get daily price! Price is unavailable.',
            ], 404);
        }

        return (new PriceResource($price))
            ->additional(['message' => 'Success get daily price!'])
            ->response();
    }
}
