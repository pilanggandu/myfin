<?php

namespace App\Http\Requests\Recurring;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmOccurrenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('recurring'));
    }

    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'transaction_date' => ['nullable', 'date'],
            'transaction_time' => ['nullable', 'string', 'max:8'],
            'source_account_id' => ['nullable', 'integer'],
            'destination_account_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.required' => 'Tanggal jatuh tempo periode wajib disertakan.',
            'amount.min' => 'Nominal transaksi minimal Rp 1.',
        ];
    }
}
