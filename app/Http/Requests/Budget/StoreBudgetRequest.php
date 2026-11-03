<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $space = app('currentSpace');
        $spaceId = $space?->id;

        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->where('type', 'expense')
                    ->whereNull('archived_at'),
                Rule::unique('budgets')->where(function ($query) use ($spaceId) {
                    return $query->where('space_id', $spaceId)
                        ->where('year', $this->year)
                        ->where('month', $this->month);
                }),
            ],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'limit_amount' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori pengeluaran wajib dipilih.',
            'category_id.exists' => 'Kategori tidak valid, bukan pengeluaran, atau telah diarsipkan.',
            'category_id.unique' => 'Anggaran untuk kategori ini pada periode bulan dan tahun yang sama sudah ada.',
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'month.required' => 'Bulan wajib dipilih.',
            'month.integer' => 'Bulan harus berupa angka 1 sampai 12.',
            'limit_amount.required' => 'Batas limit anggaran wajib diisi.',
            'limit_amount.integer' => 'Batas limit anggaran harus berupa angka rupiah.',
            'limit_amount.min' => 'Batas limit anggaran minimal Rp 1.',
        ];
    }
}
