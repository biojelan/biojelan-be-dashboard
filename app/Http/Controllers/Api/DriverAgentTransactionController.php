<?php

namespace App\Http\Controllers\Api;

use App\ClientTransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreAgentTransactionRequest;
use App\Http\Resources\DriverAgentTransactionResource;
use App\Models\Agen;
use App\Models\Price;
use App\Models\TransactionAgent;
use App\Models\User;
use App\PriceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DriverAgentTransactionController extends Controller
{
    public function store(StoreAgentTransactionRequest $request): JsonResponse
    {
        /** @var User $driver */
        $driver = $request->user();
        $agent = $this->findAgent($request);

        if ($agent === null) {
            return $this->messageResponse('Failed create transaction! Agent not registered.', 422);
        }

        if ($agent->agen === null) {
            return $this->messageResponse('Failed create transaction! Agent profile is unavailable.', 422);
        }

        $price = Price::query()
            ->where('price_type', PriceType::Agent)
            ->whereDate('start_date', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })
            ->latest('start_date')
            ->first();

        if ($price === null) {
            return $this->messageResponse('Failed create transaction! Agent price is unavailable.', 422);
        }

        $volumeLiter = (float) $request->string('volume_liter')->toString();
        $transaction = DB::transaction(function () use ($agent, $driver, $price, $volumeLiter, $request): ?TransactionAgent {
            $agentProfile = Agen::query()->lockForUpdate()->find($agent->id);

            if ($agentProfile === null || $agentProfile->stock_liter < $volumeLiter) {
                return null;
            }

            $agentProfile->stock_liter = round($agentProfile->stock_liter - $volumeLiter, 3);
            $agentProfile->save();

            return TransactionAgent::query()->create([
                'price_id' => $price->price_id,
                'agent_id' => $agent->id,
                'driver_id' => $driver->id,
                'volume_liter' => $volumeLiter,
                'total_price' => round((float) $price->price_per_liter * $volumeLiter, 2),
                'status' => ClientTransactionStatus::Pending,
                'transaction_note' => $request->safe()->input('transaction_note'),
            ]);
        });

        if ($transaction === null) {
            return $this->messageResponse('Failed create transaction! Agent stock is insufficient.', 422);
        }

        return (new DriverAgentTransactionResource($transaction->fresh(['agent', 'price'])))
            ->additional(['message' => 'Success create transaction!'])
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $driver */
        $driver = $request->user();
        $transactions = TransactionAgent::query()
            ->where('driver_id', $driver->id)
            ->with(['agent', 'price'])
            ->latest('id')
            ->get();

        return DriverAgentTransactionResource::collection($transactions)
            ->additional(['message' => 'Success get all transactions!'])
            ->response();
    }

    public function cancel(Request $request, TransactionAgent $transactionAgent): JsonResponse
    {
        /** @var User $driver */
        $driver = $request->user();
        $transaction = DB::transaction(function () use ($driver, $transactionAgent): ?TransactionAgent {
            $transaction = TransactionAgent::query()
                ->whereKey($transactionAgent->id)
                ->where('driver_id', $driver->id)
                ->lockForUpdate()
                ->first();

            abort_if($transaction === null, 404);

            if (! in_array($transaction->status, [ClientTransactionStatus::Pending, ClientTransactionStatus::Accepted], true)) {
                return null;
            }

            $transaction->status = ClientTransactionStatus::CancelRequested;
            $transaction->save();

            return $transaction;
        });

        if ($transaction === null) {
            return $this->messageResponse('Failed request cancel transaction! Transaction status is invalid.', 422);
        }

        return $this->statusResponse($transaction, 'Success request cancel transaction!');
    }

    private function findAgent(StoreAgentTransactionRequest $request): ?User
    {
        $query = User::query()
            ->where('role_id', User::AGEN_ROLE_ID)
            ->where('is_active', true)
            ->with('agen');

        if ($request->filled('agent_email')) {
            return $query->where('email', (string) $request->string('agent_email'))->first();
        }

        return $query->where('phone', (string) $request->string('agent_phone'))->first();
    }

    private function statusResponse(TransactionAgent $transaction, string $message): JsonResponse
    {
        return response()->json([
            'data' => [
                'transaction_id' => $transaction->transaction_id,
                'status' => $transaction->status->value,
            ],
            'message' => $message,
        ]);
    }

    private function messageResponse(string $message, int $status): JsonResponse
    {
        return response()->json(['data' => (object) [], 'message' => $message], $status);
    }
}
