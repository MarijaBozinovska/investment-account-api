<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in([
                'deposit',
                'withdrawal',
                'buy',
                'sell',
            ])],

            'amount' => [
                Rule::requiredIf(
                    in_array($this->input('type'), ['deposit', 'withdrawal'])
                ),
                'nullable',
                'numeric',
                'min:0.01',
            ],

            'instrument' => [
                Rule::requiredIf(
                    in_array($this->input('type'), ['buy', 'sell'])
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'quantity' => [
                Rule::requiredIf(
                    in_array($this->input('type'), ['buy', 'sell'])
                ),
                'nullable',
                'integer',
                'min:1',
            ],

            'price' => [
                Rule::requiredIf(
                    in_array($this->input('type'), ['buy', 'sell'])
                ),
                'nullable',
                'numeric',
                'min:0.01',
            ],
        ];
    }
}