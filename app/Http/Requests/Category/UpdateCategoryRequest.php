<?php

namespace App\Http\Requests\Category;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('category'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('rollover_enabled')) {
            $this->merge([
                'rollover_enabled' => $this->boolean('rollover_enabled'),
                'rollover_cap' => $this->normalizeMonetaryInput($this->input('rollover_cap')),
            ]);
        }
    }

    public function rules(): array
    {
        $space = app('currentSpace');
        $category = $this->route('category');

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->where(function ($query) use ($space, $category) {
                    return $query->where('space_id', $space?->id)
                        ->where('type', $category->type);
                })->ignore($category->id),
            ],
            'rollover_enabled' => ['nullable', 'boolean'],
            'rollover_mode' => ['nullable', 'in:all,surplus_only,deficit_only'],
            'rollover_cap' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.unique' => 'Kategori dengan nama ini sudah ada di ruang aktif.',
        ];
    }
}
