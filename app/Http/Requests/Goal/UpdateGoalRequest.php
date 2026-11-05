<?php

namespace App\Http\Requests\Goal;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('target_amount')) {
            $this->merge([
                'target_amount' => $this->normalizeMonetaryInput($this->input('target_amount')),
            ]);
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
            'status' => ['required', 'in:active,achieved,paused,cancelled'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'status.required' => 'Status target wajib dipilih.',
            'status.in' => 'Status target tidak valid.',
        ];
    }
}
