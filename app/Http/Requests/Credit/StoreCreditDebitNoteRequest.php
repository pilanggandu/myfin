<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditDebitNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credit_account_id' => ['required', 'exists:credit_accounts,id'],
            'installment_plan_id' => ['nullable', 'exists:installment_plans,id'],
            'installment_id' => ['nullable', 'exists:installments,id'],
            'type' => ['required', 'in:credit_note,debit_note'],
            'amount' => ['required', 'integer', 'min:1'],
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
            'amount.min' => 'Nominal nota minimal Rp 1.',
            'note_date.required' => 'Tanggal nota wajib diisi.',
            'description.required' => 'Keterangan/alasan nota wajib diisi.',
        ];
    }
}
