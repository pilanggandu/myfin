<?php

namespace App\Http\Requests\Goal;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class StoreGoalRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];
        if ($this->has('target_amount')) {
            $merged['target_amount'] = $this->normalizeMonetaryInput($this->input('target_amount'));
        }
        if ($this->has('initial_deposit')) {
            $merged['initial_deposit'] = $this->normalizeMonetaryInput($this->input('initial_deposit'));
        }
        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'target_amount' => ['required', 'numeric', 'min:1000'],
            'start_date' => ['required', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'account_id' => ['nullable', 'exists:accounts,id'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'icon' => ['nullable', 'string', 'max:32'],
            'color' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'initial_deposit' => ['nullable', 'numeric', 'min:0'],
            'deduct_from_account' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama target tabungan wajib diisi.',
            'target_amount.required' => 'Nominal target wajib diisi.',
            'target_amount.min' => 'Nominal target minimal Rp 1.000.',
            'start_date.required' => 'Tanggal mulai target wajib diisi.',
            'target_date.after_or_equal' => 'Tanggal target harus sama atau setelah tanggal mulai.',
        ];
    }
}
