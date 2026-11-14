<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInstallmentPlanDatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('plan'));
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'due_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'first_due_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('start_date') && ! $this->filled('due_day') && ! $this->filled('first_due_date')) {
                $validator->errors()->add('start_date', 'Harap isi minimal satu tanggal atau hari jatuh tempo yang ingin diubah.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'start_date.date' => 'Format tanggal transaksi pembelian tidak valid.',
            'due_day.integer' => 'Hari jatuh tempo bulanan harus berupa angka hari (1-31).',
            'due_day.min' => 'Hari jatuh tempo bulanan minimal tanggal 1.',
            'due_day.max' => 'Hari jatuh tempo bulanan maksimal tanggal 31.',
            'first_due_date.date' => 'Format tanggal jatuh tempo pertama tidak valid.',
        ];
    }
}
