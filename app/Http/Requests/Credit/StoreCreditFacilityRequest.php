<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditFacilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'total_limit' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama fasilitas limit kredit gabungan wajib diisi.',
            'total_limit.required' => 'Total plafon limit gabungan wajib diisi.',
            'total_limit.numeric' => 'Total plafon limit harus berupa angka.',
            'total_limit.min' => 'Total plafon limit tidak boleh negatif.',
        ];
    }
}
