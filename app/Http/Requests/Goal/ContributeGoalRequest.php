<?php

namespace App\Http\Requests\Goal;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class ContributeGoalRequest extends FormRequest
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
            'contribution_date' => ['required', 'date'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'member_id' => ['nullable', 'exists:members,id'],
            'deduct_from_account' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal setoran wajib diisi.',
            'amount.min' => 'Nominal setoran minimal Rp 100.',
            'contribution_date.required' => 'Tanggal setoran wajib diisi.',
        ];
    }
}
