<?php

namespace App\Http\Requests\Account;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class AdjustAccountBalanceRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('account'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('target_balance')) {
            $this->merge(['target_balance' => $this->normalizeMonetaryInput($this->input('target_balance'))]);
        }
    }

    public function rules(): array
    {
        return [
            'target_balance' => ['required', 'numeric', 'min:0'],
            'adjustment_date' => ['required', 'date'],
            'adjustment_time' => ['nullable', 'string', 'max:8'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_balance.required' => 'Nominal saldo riil terkini wajib diisi.',
            'target_balance.numeric' => 'Nominal saldo riil harus berupa angka yang valid.',
            'target_balance.min' => 'Nominal saldo riil tidak boleh kurang dari 0.',
            'adjustment_date.required' => 'Tanggal penyesuaian wajib diisi.',
            'adjustment_date.date' => 'Format tanggal penyesuaian tidak valid.',
            'reason.max' => 'Alasan penyesuaian maksimal 255 karakter.',
        ];
    }
}
