<?php

namespace App\Http\Requests\ShortPlan;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class StoreShortPlanItemRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('estimated_amount')) {
            $this->merge([
                'estimated_amount' => $this->normalizeMonetaryInput($this->input('estimated_amount')) ?? 0,
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'estimated_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Nama item rincian wajib diisi.',
            'estimated_amount.numeric' => 'Estimasi harga item harus berupa angka.',
            'estimated_amount.min' => 'Estimasi harga item tidak boleh negatif.',
        ];
    }
}
