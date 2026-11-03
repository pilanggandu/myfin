<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class PayCreditBillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['required', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'installment_ids' => ['nullable', 'array'],
            'installment_ids.*' => ['integer', 'exists:installments,id'],
            'redirect_to' => ['nullable', 'string', 'in:accounts,statement'],
            'month' => ['nullable', 'string', 'max:7'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_account_id.required' => 'Pilih rekening sumber pembayaran.',
            'source_account_id.exists' => 'Rekening sumber tidak valid.',
            'amount.required' => 'Nominal pembayaran tagihan wajib diisi.',
            'amount.integer' => 'Nominal pembayaran harus berupa angka bulat dalam Rupiah.',
            'amount.min' => 'Nominal pembayaran minimal Rp 1.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date' => 'Format tanggal pembayaran tidak valid.',
        ];
    }
}
