<?php

namespace App\Http\Requests\Credit;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class PayCreditBillRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:0.01'],
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
            'amount.numeric' => 'Nominal pembayaran tagihan harus berupa angka valid.',
            'amount.min' => 'Nominal pembayaran minimal Rp 0,01.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_date.date' => 'Format tanggal pembayaran tidak valid.',
        ];
    }
}
