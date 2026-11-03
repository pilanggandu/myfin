<?php

namespace App\Http\Requests\Space;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('space'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama ruang wajib diisi.',
            'name.max' => 'Nama ruang maksimal 100 karakter.',
            'timezone.timezone' => 'Zona waktu tidak valid.',
        ];
    }
}
