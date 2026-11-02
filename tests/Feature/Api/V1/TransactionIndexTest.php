<?php 

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TransactionIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);

        $this->wallet = Wallet::create([
            'user_id' => $this->user->id,
            'name' => 'Cash',
            'currency' => 'USD',
            'balance' => 10000,
        ]);
    }

    public function test_lists_paginated_transactions_with_cursor(): void
    {
        // Seed 3 transactions
        for ($i = 1; $i <= 3; $i++) {
            Transaction::create([
                'user_id' => $this->user->id,
                'wallet_id' => $this->wallet->id,
                'type' => 'expense',
                'amount' => 1000 * $i,
                'transaction_date' => now()->subDays($i)->toDateString(),
            ]);
        }

        $response = $this->getJson('/api/v1/transactions?per_page=2');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data',
                'meta' => ['path', 'per_page', 'next_cursor', 'prev_cursor'],
            ]);
    }

    public function test_filters_transactions_by_type(): void
    {
        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'type' => 'income',
            'amount' => 5000,
            'transaction_date' => now()->toDateString(),
        ]);

        Transaction::create([
            'user_id' => $this->user->id,
            'wallet_id' => $this->wallet->id,
            'type' => 'expense',
            'amount' => 2000,
            'transaction_date' => now()->toDateString(),
        ]);

        $response = $this->getJson('/api/v1/transactions?type=income');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'income');
    }

    public function test_cannot_filter_by_another_users_wallet(): void
    {
        $otherUser = User::factory()->create();
        $otherWallet = Wallet::create([
            'user_id' => $otherUser->id,
            'name' => 'Private',
            'currency' => 'USD',
            'balance' => 5000,
        ]);

        $response = $this->getJson("/api/v1/transactions?wallet_id={$otherWallet->id}");

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['wallet_id']);
    }
}