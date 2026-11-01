<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TransferFundsRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransferController extends Controller
{
    public function __invoke(TransferFundsRequest $request): JsonResponse
    {
        $sourceId = $request->validated('source_wallet_id');
        $targetId = $request->validated('target_wallet_id');
        $amount = $request->validated('amount');

        $result = DB::transaction(function () use ($request, $sourceId, $targetId, $amount) {
            $firstId = min($sourceId, $targetId);
            $secondId = max($sourceId, $targetId);

            $walletA = Wallet::where('id', $firstId)->lockForUpdate()->first();
            $walletB = Wallet::where('id', $secondId)->lockForUpdate()->first();

            $sourceWallet = ($walletA->id == $sourceId) ? $walletA : $walletB;
            $targetWallet = ($walletA->id == $targetId) ? $walletA : $walletB;

            if ($sourceWallet->currency != $targetWallet->currency) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Cross-currency transfer are not supported.');
            }

            if ($sourceWallet->balance < $amount) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Insufficient funds in the source wallet.');
            }

            $sourceWallet->decrement('balance', $amount);
            $targetWallet->increment('balance', $amount);

            return Transaction::create([
                'user_id' => $request->user()->id,
                'wallet_id' => $sourceWallet->id,
                'target_wallet_id' => $targetWallet->id,
                'type' => 'transfer',
                'amount' => $amount,
                'transaction_date' => $request->validated('transaction_date', now()->toDateString()),
                'description' => $request->validated('description', 'Wallet to wallet transfer'),
            ]);
        });

        return (new TransactionResource($result->load(['wallet', 'targetWallet'])))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
