<?php

namespace App\Http\Controllers\Api;

use App\ClientTransactionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\DriverAgentTransactionResource;
use App\Models\Agen;
use App\Models\TransactionAgent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AgentDriverTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $agent */
        $agent = $request->user();
        $status = $request->query('status');

        if ($status !== null && ClientTransactionStatus::tryFrom((string) $status) === null) {
            return $this->messageResponse('Failed get transaction! Invalid transaction status.', 422);
        }

        $transactions = TransactionAgent::query()
            ->where('agent_id', $agent->id)
            ->with(['agent', 'price'])
            ->when($status !== null, fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->get();

        $message = $status === null
            ? 'Success get all transactions!'
            : 'Success get '.mb_strtolower((string) $status).' transactions!';

        return DriverAgentTransactionResource::collection($transactions)
            ->additional(['message' => $message])
            ->response();
    }

    public function accept(Request $request, TransactionAgent $transactionAgent): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionAgent,
            ClientTransactionStatus::Pending,
            ClientTransactionStatus::Accepted,
            'Success accept transaction!',
        );
    }

    public function reject(Request $request, TransactionAgent $transactionAgent): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionAgent,
            ClientTransactionStatus::Pending,
            ClientTransactionStatus::Rejected,
            'Success reject transaction!',
            restoreStock: true,
        );
    }

    public function acceptCancellation(Request $request, TransactionAgent $transactionAgent): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionAgent,
            ClientTransactionStatus::CancelRequested,
            ClientTransactionStatus::Cancelled,
            'Success accept cancel transaction!',
            restoreStock: true,
        );
    }

    public function rejectCancellation(Request $request, TransactionAgent $transactionAgent): JsonResponse
    {
        return $this->changeStatus(
            $request,
            $transactionAgent,
            ClientTransactionStatus::CancelRequested,
            ClientTransactionStatus::Accepted,
            'Success reject cancel transaction!',
        );
    }

    private function changeStatus(
        Request $request,
        TransactionAgent $transactionAgent,
        ClientTransactionStatus $from,
        ClientTransactionStatus $to,
        string $message,
        bool $restoreStock = false,
    ): JsonResponse {
        /** @var User $agent */
        $agent = $request->user();
        $transaction = DB::transaction(function () use ($agent, $transactionAgent, $from, $to, $restoreStock): ?TransactionAgent {
            $transaction = TransactionAgent::query()
                ->whereKey($transactionAgent->id)
                ->where('agent_id', $agent->id)
                ->lockForUpdate()
                ->first();

            abort_if($transaction === null, 404);

            if ($transaction->status !== $from) {
                return null;
            }

            if ($restoreStock) {
                $agentProfile = Agen::query()->lockForUpdate()->find($agent->id);
                abort_if($agentProfile === null, 404);

                $agentProfile->stock_liter = round($agentProfile->stock_liter + (float) $transaction->volume_liter, 3);
                $agentProfile->save();
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
