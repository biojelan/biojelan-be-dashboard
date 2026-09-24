<?php

namespace App\Http\Controllers\Api;

use App\ClientTransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CheckClientByEmailRequest;
use App\Http\Requests\Api\CheckClientByPhoneRequest;
use App\Http\Requests\Api\StoreClientTransactionRequest;
use App\Http\Resources\AgentClientTransactionResource;
use App\Models\Price;
use App\Models\TransactionClient;
use App\Models\User;
use App\PriceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentClientTransactionController extends Controller
{
    private const string GUEST_CLIENT_EMAIL = 'guest.client@biojelan.id';

    public function store(StoreClientTransactionRequest $request): JsonResponse
    {
        /** @var User $agent */
        $agent = $request->user();
        $client = $this->findClient($request);

        if ($client === null && (string) $request->string('client_email') !== self::GUEST_CLIENT_EMAIL) {
            return $this->messageResponse('Failed create transaction! Client not registered.', 422);
        }

        $price = Price::query()
            ->where('price_type', PriceType::Client)
            ->whereDate('start_date', '<=', today())
            ->where(function ($query): void {
                $query->whereNull('end_date')->orWhereDate('end_date', '>=', today());
            })
            ->latest('start_date')
            ->first();

        if ($price === null) {
            return $this->messageResponse('Failed create transaction! Client price is unavailable.', 422);
        }

        $volumeLiter = (string) $request->string('volume_liter');
        $transaction = DB::transaction(function () use ($agent, $client, $price, $volumeLiter, $request): TransactionClient {
            return TransactionClient::query()->create([
                'price_id' => $price->price_id,
                'client_id' => $client?->id,
                'agent_id' => $agent->id,
                'volume_liter' => $volumeLiter,
                'total_price' => round((float) $price->price_per_liter * (float) $volumeLiter, 2),
                'status' => ClientTransactionStatus::Pending,
                'transaction_note' => $request->safe()->input('transaction_note'),
            ]);
        });

        return (new AgentClientTransactionResource($transaction->fresh(['client', 'price'])))
            ->additional(['message' => 'Success create transaction!'])
            ->response()
            ->setStatusCode(201);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var User $agent */
        $agent = $request->user();
        $transactions = TransactionClient::query()
            ->where('agent_id', $agent->id)
            ->with(['client', 'price'])
            ->latest('id')
            ->get();

        return AgentClientTransactionResource::collection($transactions)
            ->additional(['message' => 'Success get all transactions!'])
            ->response();
    }

    public function checkByEmail(CheckClientByEmailRequest $request): JsonResponse
    {
        $client = User::query()
            ->where('email', (string) $request->string('client_email'))
            ->where('role_id', User::CLIENT_ROLE_ID)
            ->first();

        return $this->clientCheckResponse($client);
    }

    public function checkByPhone(CheckClientByPhoneRequest $request): JsonResponse
    {
        $client = User::query()
            ->where('phone', (string) $request->string('client_phone'))
            ->where('role_id', User::CLIENT_ROLE_ID)
            ->first();

        return $this->clientCheckResponse($client);
    }

    public function cancel(Request $request, TransactionClient $transactionClient): JsonResponse
    {
        /** @var User $agent */
        $agent = $request->user();
        $transaction = DB::transaction(function () use ($agent, $transactionClient): ?TransactionClient {
            $transaction = TransactionClient::query()
                ->whereKey($transactionClient->id)
                ->where('agent_id', $agent->id)
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

    private function findClient(StoreClientTransactionRequest $request): ?User
    {
        if ($request->filled('client_email')) {
            $email = (string) $request->string('client_email');

            if ($email === self::GUEST_CLIENT_EMAIL) {
                return null;
            }

            return User::query()
                ->where('email', $email)
                ->where('role_id', User::CLIENT_ROLE_ID)
                ->where('is_active', true)
                ->first();
        }

        return User::query()
            ->where('phone', (string) $request->string('client_phone'))
            ->where('role_id', User::CLIENT_ROLE_ID)
            ->where('is_active', true)
            ->first();
    }

    private function clientCheckResponse(?User $client): JsonResponse
    {
        return response()->json([
            'data' => [
                'is_exist' => $client !== null,
                'client_id' => $client?->id,
                'client_name' => $client?->name,
                'client_email' => $client?->email,
                'client_phone' => $client?->phone,
            ],
            'message' => 'Success check clients email or phone!',
        ]);
    }

    private function statusResponse(TransactionClient $transaction, string $message): JsonResponse
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
