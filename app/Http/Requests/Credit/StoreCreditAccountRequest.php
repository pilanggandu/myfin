<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:credit_card,paylater,loan'],
            'credit_limit' => ['required', 'integer', 'min:0'],
            'billing_cycle_day' => ['nullable', 'integer', 'between:1,31'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kartu atau paylater wajib diisi.',
            'type.required' => 'Tipe kredit wajib dipilih.',
            'type.in' => 'Tipe kredit harus berupa Kartu Kredit, PayLater, atau Pinjaman.',
            'credit_limit.required' => 'Batas limit kredit wajib diisi.',
            'credit_limit.integer' => 'Batas limit kredit harus berupa angka bulat dalam Rupiah.',
            'credit_limit.min' => 'Batas limit kredit tidak boleh negatif.',
            'billing_cycle_day.between' => 'Tanggal cetak tagihan harus antara 1 sampai 31.',
            'due_day.between' => 'Tanggal jatuh tempo harus antara 1 sampai 31.',
        ];
    }
}
