<?php

namespace App\Http\Requests\ShortPlan;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class RealizeShortPlanRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [
            'record_transaction' => $this->boolean('record_transaction'),
        ];

        if ($this->has('actual_amount')) {
            $merged['actual_amount'] = $this->normalizeMonetaryInput($this->input('actual_amount'));
        }

        $this->merge($merged);
    }

    public function rules(): array
    {
        $spaceId = app('currentSpace')?->id;

        return [
            'record_transaction' => ['required', 'boolean'],
            'actual_amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['nullable', 'date'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment'])
                    ->whereNull('archived_at'),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->where('type', 'expense')
                    ->whereNull('archived_at'),
            ],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'request_key' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('record_transaction')) {
                    return;
                }

                $actualAmount = $this->input('actual_amount');
                if ($actualAmount === null || (float) $actualAmount < 1) {
                    $validator->errors()->add('actual_amount', 'Nominal realisasi wajib minimal Rp 1 untuk mencatat transaksi.');
                }

                if (! $this->filled('transaction_date')) {
                    $validator->errors()->add('transaction_date', 'Tanggal transaksi realisasi wajib diisi.');
                }

                if (! $this->filled('account_id')) {
                    $validator->errors()->add('account_id', 'Rekening sumber wajib dipilih untuk mencatat transaksi.');
                }

                if (! $this->filled('category_id')) {
                    $validator->errors()->add('category_id', 'Kategori pengeluaran wajib dipilih untuk mencatat transaksi.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'actual_amount.numeric' => 'Nominal realisasi harus berupa angka yang valid.',
            'actual_amount.min' => 'Nominal realisasi tidak boleh negatif.',
            'transaction_date.date' => 'Format tanggal transaksi tidak valid.',
            'account_id.exists' => 'Rekening sumber tidak valid atau sudah diarsipkan.',
            'category_id.exists' => 'Kategori pengeluaran tidak valid atau sudah diarsipkan.',
            'member_id.exists' => 'Member yang dipilih tidak valid atau sudah diarsipkan.',
        ];
    }
}
