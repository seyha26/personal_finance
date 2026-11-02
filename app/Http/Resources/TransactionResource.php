<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount_in_cents' => $this->amount,
            'amount_formatted' => number_format($this->amount / 100, 2),
            'currency' => $this->wallet->currency ?? 'USD',
            'source_wallet_id' => $this->wallet_id,
            'target_wallet_id' => $this->target_wallet_id,
            'source_wallet' => [
                'id' => $this->wallet->id,
                'name' => $this->wallet->name
            ],
            'target_wallet' => $this->when($this->target_wallet_id, fn () => [
                'id' => $this->targetWallet->id,
                'name' => $this->targetWallet->name
            ]),
            'transaction_date' => $this->transaction_date->toDateString(),
            'description' => $this->description,
            'created_at' => $this->created_at->toISOString()
        ];
    }
}
