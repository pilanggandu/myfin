<?php

namespace App\Http\Requests\PlannedIncome;

use App\Models\PlannedIncome;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RealizePlannedIncomeRequest extends FormRequest
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
            'actual_amount' => ['required', 'numeric', 'min:1'],
            'record_transaction' => ['nullable', 'boolean'],
            'account_id' => [
                'nullable',
                'required_if:record_transaction,true',
                'integer',
                Rule::exists('accounts', 'id')->where(function ($query) use ($spaceId) {
                    $query->where('space_id', $spaceId)->whereNull('archived_at');
                }),
            ],
            'category_id' => [
                'nullable',
                'required_if:record_transaction,true',
                'integer',
                Rule::exists('categories', 'id')->where(function ($query) use ($spaceId) {
                    $query->where('space_id', $spaceId)->where('type', 'income')->whereNull('archived_at');
                }),
            ],
            'received_date' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'actual_amount.required' => 'Nominal riil yang diterima wajib diisi.',
            'actual_amount.min' => 'Nominal riil harus minimal 1.',
            'account_id.required_if' => 'Rekening penerimaan wajib dipilih jika mencatat transaksi kas.',
            'category_id.required_if' => 'Kategori pemasukan wajib dipilih jika mencatat transaksi kas.',
        ];
    }
}
