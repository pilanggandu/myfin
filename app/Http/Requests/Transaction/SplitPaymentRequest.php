<?php

namespace App\Http\Requests\Transaction;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class SplitPaymentRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('payments') && is_array($this->input('payments'))) {
            $payments = $this->input('payments');
            foreach ($payments as $key => $payment) {
                if (isset($payment['amount'])) {
                    $payments[$key]['amount'] = $this->normalizeMonetaryInput($payment['amount']);
                }
            }
            $this->merge(['payments' => $payments]);
        }
    }

    public function rules(): array
    {
        return [
            'payments' => ['required', 'array', 'min:2'],
            'payments.*.account_id' => ['required', 'integer', 'distinct'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'payments.required' => 'Daftar alokasi rekening pembayaran wajib diisi.',
            'payments.array' => 'Format alokasi rekening pembayaran tidak valid.',
            'payments.min' => 'Split payment (multi-rekening) membutuhkan minimal 2 rekening pembayaran.',
            'payments.*.account_id.required' => 'Rekening pada setiap baris pembayaran wajib dipilih.',
            'payments.*.account_id.integer' => 'Rekening yang dipilih tidak valid.',
            'payments.*.account_id.distinct' => 'Rekening pembayaran tidak boleh dipilih lebih dari sekali. Gabungkan ke satu baris.',
            'payments.*.amount.required' => 'Nominal pada setiap baris pembayaran wajib diisi.',
            'payments.*.amount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'payments.*.amount.min' => 'Nominal pembayaran minimal Rp 0,01.',
        ];
    }
}
