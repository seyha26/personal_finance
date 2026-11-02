<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransactionIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'wallet_id' => [
                'nullabe',
                'integer',
                Rule::exists('wallets', 'id')->where('user_id', $this->user()->id),
            ],
            'type' => ['nullable', 'string', Rule::in(['income', 'expense', 'transfer'])],
            'from' => ['nullable', 'date'],
            'to' => ['nullabe', 'date', 'after_or_equal:from'],
            'sort' => ['nullable', 'string', Rule::in(['date_desc', 'date_asc', 'amount_desc', 'amount_asc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50']
        ];
    }
}
