<?php

namespace App\Http\Requests\Credit;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class PayInstallmentRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge(['amount' => $this->normalizeMonetaryInput($this->input('amount'))]);
        }
    }

    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'exists:accounts,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_account_id.required' => 'Pilih rekening kas/bank untuk pembayaran.',
            'source_account_id.exists' => 'Rekening sumber dana tidak ditemukan.',
            'amount.numeric' => 'Nominal pembayaran harus berupa angka valid.',
            'amount.min' => 'Nominal pembayaran minimal Rp 0,01.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date' => 'Format tanggal pembayaran tidak valid.',
        ];
    }
}
