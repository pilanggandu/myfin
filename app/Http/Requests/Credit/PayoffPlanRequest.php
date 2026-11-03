<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class PayoffPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_account_id' => ['required', 'exists:accounts,id'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_account_id.required' => 'Pilih rekening sumber dana untuk pelunasan sekaligus.',
            'source_account_id.exists' => 'Rekening sumber dana tidak valid.',
            'payment_date.required' => 'Tanggal pelunasan wajib diisi.',
            'payment_date.date' => 'Format tanggal pelunasan tidak valid.',
        ];
    }
}
