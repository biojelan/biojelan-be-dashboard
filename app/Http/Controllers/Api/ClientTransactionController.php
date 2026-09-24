<?php

namespace App\Http\Controllers\Api;

use App\ClientTransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ClientTransactionResource;
use App\Models\TransactionClient;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $client */
        $client = $request->user();
        $status = $request->query('status');

        if ($status !== null && ClientTransactionStatus::tryFrom((string) $status) === null) {
            return $this->messageResponse('Failed get transaction! Invalid transaction status.', 422);
        }

        $transactions = TransactionClient::query()
            ->where('client_id', $client->id)
            ->with(['agent', 'price'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->get();

        $message = $status === null
            ? 'Success get all transactions!'
            : 'Success get transaction status '.mb_strtolower((string) $status).'!';

        return ClientTransactionResource::collection($transactions)
            ->additional(['message' => $message])
            ->response();
    }

    public function accept(Request $request, TransactionClient $transactionClient): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionClient,
            ClientTransactionStatus::Pending,
            ClientTransactionStatus::Accepted,
            'Success accept transaction!',
        );
    }

    public function reject(Request $request, TransactionClient $transactionClient): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionClient,
            ClientTransactionStatus::Pending,
            ClientTransactionStatus::Rejected,
            'Success reject transaction!',
        );
    }

    public function acceptCancellation(Request $request, TransactionClient $transactionClient): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionClient,
            ClientTransactionStatus::CancelRequested,
            ClientTransactionStatus::Cancelled,
            'Success accept cancel transaction!',
        );
    }

    public function rejectCancellation(Request $request, TransactionClient $transactionClient): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionClient,
            ClientTransactionStatus::CancelRequested,
            ClientTransactionStatus::Accepted,
            'Success reject cancel transaction!',
        );
    }

    private function changeStatus(
        Request $request,
        TransactionClient $transactionClient,
        ClientTransactionStatus $from,
        ClientTransactionStatus $to,
        string $message,
    ): JsonResponse {
        /** @var User $client */
        $client = $request->user();
        $transaction = DB::transaction(function () use ($client, $transactionClient, $from, $to): ?TransactionClient {
            $transaction = TransactionClient::query()
                ->whereKey($transactionClient->id)
                ->where('client_id', $client->id)
                ->lockForUpdate()
                ->first();

            abort_if($transaction === null, 404);

            if ($transaction->status !== $from) {
                return null;
            }

            $transaction->status = $to;
            $transaction->save();

            return $transaction;
        });

        if ($transaction === null) {
            return $this->messageResponse('Failed update transaction! Transaction status is invalid.', 422);
        }

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
