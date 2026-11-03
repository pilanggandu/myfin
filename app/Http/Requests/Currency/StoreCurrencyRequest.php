<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:3', 'alpha', Rule::unique('currencies', 'code')],
            'name' => ['required', 'string', 'max:100'],
            'symbol' => ['required', 'string', 'max:10'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'decimal_digits' => ['required', 'integer', 'min:0', 'max:6'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode mata uang wajib diisi.',
            'code.size' => 'Kode mata uang harus 3 karakter huruf kapital (misal: USD, EUR, JPY).',
            'code.alpha' => 'Kode mata uang hanya boleh berupa huruf.',
            'code.unique' => 'Kode mata uang sudah terdaftar di master data.',
            'name.required' => 'Nama mata uang wajib diisi.',
            'name.max' => 'Nama mata uang maksimal 100 karakter.',
            'symbol.required' => 'Simbol mata uang wajib diisi (misal: $, Rp, €).',
            'symbol.max' => 'Simbol mata uang maksimal 10 karakter.',
            'exchange_rate.numeric' => 'Nilai kurs harus berupa angka positif.',
            'exchange_rate.min' => 'Nilai kurs minimal 0.000001 terhadap Rupiah.',
            'decimal_digits.required' => 'Jumlah digit desimal wajib diisi.',
            'decimal_digits.integer' => 'Jumlah digit desimal harus berupa angka.',
            'decimal_digits.min' => 'Digit desimal minimal 0.',
            'decimal_digits.max' => 'Digit desimal maksimal 6.',
        ];
    }
}
