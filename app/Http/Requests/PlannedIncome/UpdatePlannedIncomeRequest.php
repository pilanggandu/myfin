<?php

namespace App\Http\Requests\PlannedIncome;

use App\Models\PlannedIncome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlannedIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plannedIncome = $this->route('plannedIncome');

        return $plannedIncome instanceof PlannedIncome
            && $plannedIncome->space->hasActiveMember($this->user());
    }

    public function rules(): array
    {
        $plannedIncome = $this->route('plannedIncome');
        $spaceId = $plannedIncome?->space_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:1'],
            'expected_date' => ['required', 'date'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where(function ($query) use ($spaceId) {
                    $query->where('space_id', $spaceId)->whereNull('archived_at');
                }),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($spaceId) {
                    $query->where('space_id', $spaceId)->where('type', 'income')->whereNull('archived_at');
                }),
            ],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')->where('space_id', $spaceId),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul rencana pemasukan wajib diisi.',
            'amount.required' => 'Nominal rencana pemasukan wajib diisi.',
            'amount.min' => 'Nominal rencana pemasukan harus minimal 1.',
            'expected_date.required' => 'Perkiraan tanggal pemasukan wajib diisi.',
            'account_id.exists' => 'Rekening penerimaan tidak valid atau telah diarsipkan.',
            'category_id.exists' => 'Kategori pemasukan tidak valid atau telah diarsipkan.',
        ];
    }
}
