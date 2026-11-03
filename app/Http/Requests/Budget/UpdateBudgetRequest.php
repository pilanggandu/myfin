<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('budget'));
    }

    public function rules(): array
    {
        return [
            'limit_amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit_amount.required' => 'Batas limit anggaran wajib diisi.',
            'limit_amount.integer' => 'Batas limit anggaran harus berupa angka rupiah.',
            'limit_amount.min' => 'Batas limit anggaran minimal Rp 1.',
        ];
    }
}
