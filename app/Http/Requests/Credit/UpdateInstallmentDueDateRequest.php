<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstallmentDueDateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $installment = $this->route('installment');

        return $installment && $this->user()->can('update', $installment->installmentPlan);
    }

    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.required' => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
        ];
    }
}
