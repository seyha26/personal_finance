<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TransactionIndexRequest;
use App\Http\Resources\TransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function index(TransactionIndexRequest $request): AnonymousResourceCollection
    {
        $query = $request->user()->transactions()
            ->with(['wallet', 'targetWallet', 'category']);

        $query->when($request->validated('wallet_id'), function ($q, $walletId) {
            $q->where(function ($sub) use ($walletId) {
                $sub->where('wallet_id', $walletId)
                    ->orWhere('target_wallet_id', $walletId);
            });
        });

        $query->when($request->validated('type'),fn ($q, $type) => $q->where('type', $type));
        $query->when($request->validated('from'), fn ($q, $from) => $q->whereDate('transaction_date', '>=', $from));
        $query->when($request->validated('to'), fn ($q, $to) => $q->whereDate('transaction_date', '<=', $to));

        match ($request->validated('sort', 'date_desc')) {
            'date_asc' => $query->orderBy('transaction_date', 'asc')->orderBy('id', 'asc'),
            'amount_desc' => $query->orderBy('amount', 'desc')->orderBy('id', 'desc'),
            'amount_asc' => $query->orderBy('amount', 'asc')->orderBy('id', 'asc'),
            default => $query->orderBy('transaction_date', 'desc')->orderBy('id', 'desc'),
        };

        return TransactionResource::collection(
            $query->cursorPaginate($request->validated('per_page', 10))
        );
    }
}
