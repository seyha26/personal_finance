<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferFundsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;
        return [
            'source_wallet_id' => [
                'required',
                'integer',
                Rule::exists('wallets', 'id')->where(fn ($query) => $query->where('user_id', $userId))
            ],
            'target_wallet_id' => [
                'required',
                'integer',
                'different:source_wallet_id',
                Rule::exists('wallets', 'id')->where(fn ($query) => $query->where('user_id', $userId))
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255']
        ];
    }
}
