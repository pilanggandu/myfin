<?php

namespace App\Http\Requests\Goal;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawGoalRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge([
                'amount' => $this->normalizeMonetaryInput($this->input('amount')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:100'],
            'withdrawal_date' => ['required', 'date'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'deposit_to_account' => ['nullable', 'boolean'],
            'member_id' => ['nullable', 'exists:members,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal penarikan wajib diisi.',
            'amount.min' => 'Nominal penarikan minimal Rp 100.',
            'withdrawal_date.required' => 'Tanggal penarikan wajib diisi.',
        ];
    }
}
