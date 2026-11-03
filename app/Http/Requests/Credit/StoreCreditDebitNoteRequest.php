<?php

namespace App\Http\Requests\Credit;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class StoreCreditDebitNoteRequest extends FormRequest
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
            'credit_account_id' => ['required', 'exists:credit_accounts,id'],
            'installment_plan_id' => ['nullable', 'exists:installment_plans,id'],
            'installment_id' => ['nullable', 'exists:installments,id'],
            'type' => ['required', 'in:credit_note,debit_note'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'note_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'source_account_id' => ['nullable', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Pilih tipe nota (Nota Kredit atau Nota Debit).',
            'amount.required' => 'Nominal nota wajib diisi.',
            'amount.numeric' => 'Nominal nota harus berupa angka valid.',
            'amount.min' => 'Nominal nota minimal Rp 0,01.',
            'note_date.required' => 'Tanggal nota wajib diisi.',
            'description.required' => 'Keterangan/alasan nota wajib diisi.',
        ];
    }
}
