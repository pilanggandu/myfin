<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class PayInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['nullable', 'integer', 'min:1'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_account_id.required' => 'Pilih rekening kas/bank untuk pembayaran.',
            'source_account_id.exists' => 'Rekening sumber dana tidak ditemukan.',
            'amount.integer' => 'Nominal pembayaran harus berupa angka bulat dalam Rupiah.',
            'amount.min' => 'Nominal pembayaran minimal Rp 1.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date' => 'Format tanggal pembayaran tidak valid.',
        ];
    }
}
