<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\Sanctum;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Wallet $sourceWallet;
    private Wallet $targetWallet;
    /**
     * A basic feature test example.
     */
    protected function setUP(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        // Sanctum::actingAs($this->user);
        Sanctum::actingAs($this->user);

        $this->sourceWallet = Wallet::create([
            'user_id' => $this->user->id,
            'name' => 'Checking',
            'currency' => 'USD',
            'balance' => 10000
        ]);

        $this->targetWallet = Wallet::create([
            'user_id' => $this->user->id,
            'name' => 'Savings',
            'currency' => 'USD',
            'balance' => 5000
        ]);
    }

    public function test_transfers_funds_successfully_between_wallets(): void
    {
        $payload = [
            'source_wallet_id' => $this->sourceWallet->id,
            'target_wallet_id' => $this->targetWallet->id,
            'amount' => 4000,
            'description' => 'Monthly savings allocation.'
        ];

        $response = $this->postJson('/api/v1/wallets/transfer', $payload);

        $response->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.amount_in_cents', 4000)
            ->assertJsonPath('data.amount_formatted', '40.00')
            ->assertJsonPath('data.source_wallet_id', $this->sourceWallet->id)
            ->assertJsonPath('data.target_wallet_id', $this->targetWallet->id);

        $this->assertDatabaseHas('wallets', [
            'id' => $this->sourceWallet->id,
            'balance' => 6000
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $this->targetWallet->id,
            'balance' => 9000
        ]);

        $w = Wallet::find($this->sourceWallet->id);
        Log::info($w);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'wallet_id' => $this->sourceWallet->id,
            'target_wallet_id' => $this->targetWallet->id,
            'type' => 'transfer',
            'amount' => 4000
        ]);
    }

    public function test_fails_and_rolls_back_when_funds_are_insufficient(): void
    {
        $payload = [
            'source_wallet_id' => $this->sourceWallet->id,
            'target_wallet_id' => $this->targetWallet->id,
            'amount' => 15000, // $150.00 (Exceeds $100 balance)
        ];

        $response = $this->postJson('/api/v1/wallets/transfer', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        // Verify balances were completely rolled back and remain untouched
        $this->assertDatabaseHas('wallets', [
            'id' => $this->sourceWallet->id,
            'balance' => 10000,
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $this->targetWallet->id,
            'balance' => 5000,
        ]);

        // No ledger transaction should be recorded
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cannot_transfer_to_the_same_wallet(): void
    {
        $payload = [
            'source_wallet_id' => $this->sourceWallet->id,
            'target_wallet_id' => $this->sourceWallet->id, // Identical ID
            'amount' => 1000,
        ];

        $response = $this->postJson('/api/v1/wallets/transfer', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['target_wallet_id']);
    }

    public function test_cannot_transfer_from_wallet_owned_by_another_user(): void
    {
        $otherUser = User::factory()->create();
        $victimWallet = Wallet::create([
            'user_id' => $otherUser->id,
            'name' => 'Stranger Checking',
            'currency' => 'USD',
            'balance' => 50000,
        ]);

        $payload = [
            'source_wallet_id' => $victimWallet->id, // Unauthorized wallet
            'target_wallet_id' => $this->targetWallet->id,
            'amount' => 1000,
        ];

        $response = $this->postJson('/api/v1/wallets/transfer', $payload);

        // FormRequest exists validation fails because user_id does not match
        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['source_wallet_id']);
    }
}
