<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $user = User::firstOrCreate(
            ['email' => 'seyha@example.com'],
            [
                'name' => 'Seyha',
                'password' => Hash::make('password')
            ]
        );

        $token = $user->createToken('dev-token')->plainTextToken;
        $this->command->info("Test user tokein: {$token}");

        $customCategories = [
            ['name' => 'Coffee & Cafes', 'type' => 'expense', 'icon' => 'mug-hot'],
            ['name' => 'Hardware & Tech', 'type' => 'expense', 'icon' => 'server']
        ];

        foreach ( $customCategories as $cate) {
            Category::firstOrCreate(
                ['name' => $cate['name'], 'user_id' => $user->id],
                [
                    'type' => $cate['type'],
                    'icon' => $cate['icon']
                ]
            );
        }

        $checkingWallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Main Checking'],
            [
                'currency' => 'USD',
                'balance' => 150000,
                'color' => "#3B82F6"
            ]
        );

        $savingsWallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Savings Account'],
            [
                'currency' => 'USD',
                'balance' => 85000,
                'color' => '#10B981'
            ]
        );

        $cashWallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Physical Cash'],
            [
                'currency' => 'USD',
                'balance' => 200000,
                'color' => '#F59E0B'
            ]
        );

        $salaryCategory = Category::where('name', 'Salary')->first();
        $groceriesCategory = Category::where('name', 'Groceries')->first();


        Transaction::firstOrCreate(
            [
                'user_id' => $user->id,
                'wallet_id' => $checkingWallet->id,
                'type' => 'income',
                'amount' => 200000,
                'transaction_date' => now()->subDays(5)->toDateString()
            ],
            [
                'category_id' => $salaryCategory?->id,
                'description' => 'Monthly Salary Deposit'
            ]
        );

        Transaction::firstOrCreate(
            [
                'user_id' => $user->id,
                'wallet_id' => $checkingWallet->id,
                'type' => 'expense',
                'amount' => 50000,
                'transaction_date' => now()->subDay(3)->toDateString()
            ],
            [
                'category_id' => $groceriesCategory?->id,
                'description' => 'Weekly SuperMarket Haul'
            ]
        );

        Transaction::firstOrCreate(
            [
                'user_id' => $user->id,
                'wallet_id' => $checkingWallet->id,
                'target_wallet_id' => $savingsWallet->id,
                'type' => 'transfer',
                'amount' => 35000,
                'transaction_date' => now()->subDay()->toDateString(),
            ],
            [
                'description' => 'Automatic Transfer to Savings'
            ]
        );
    }
}
