<?php

namespace App\Http\Requests\Recurring;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecurringScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $spaceId = app('currentSpace')?->id;
        $type = $this->input('type', 'expense');
        $isVariable = $this->boolean('is_variable_amount');

        $rules = [
            'type' => ['required', 'string', Rule::in(['expense', 'transfer', 'credit_payment'])],
            'name' => ['required', 'string', 'max:255'],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
            'source_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at')
                    ->where(function ($q) {
                        $q->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment'])
                            ->orWhere(function ($creditQ) {
                                $creditQ->whereIn('type', ['credit_card', 'paylater'])
                                    ->whereExists(function ($existsQ) {
                                        $existsQ->selectRaw('1')
                                            ->from('credit_accounts')
                                            ->whereColumn('credit_accounts.account_id', 'accounts.id')
                                            ->whereNull('credit_accounts.archived_at');
                                    });
                            });
                    }),
            ],
            'frequency' => ['required', 'string', Rule::in(['weekly', 'monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_variable_amount' => ['nullable', 'boolean'],
            'amount' => [
                Rule::requiredIf(! $isVariable),
                'nullable',
                'integer',
                'min:1',
            ],
            'description' => ['nullable', 'string', 'max:500'],
        ];

        if ($type === 'expense') {
            $rules['category_id'] = [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->where('type', 'expense')
                    ->whereNull('archived_at'),
            ];
        } elseif ($type === 'transfer') {
            $rules['source_account_id'] = [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at')
                    ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment']),
            ];
            $rules['destination_account_id'] = [
                'required',
                'integer',
                'different:source_account_id',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at')
                    ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment']),
            ];
        } elseif ($type === 'credit_payment') {
            $rules['credit_account_id'] = [
                'required',
                'integer',
                Rule::exists('credit_accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ];
            $rules['source_account_id'] = [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at')
                    ->whereIn('type', ['bank', 'cash', 'e-wallet']),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Jenis jadwal berulang wajib dipilih.',
            'name.required' => 'Nama jadwal tagihan atau transfer wajib diisi.',
            'source_account_id.required' => 'Rekening sumber wajib dipilih.',
            'source_account_id.exists' => 'Rekening sumber tidak valid untuk jenis jadwal ini.',
            'destination_account_id.required' => 'Rekening tujuan transfer wajib dipilih.',
            'destination_account_id.different' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
            'destination_account_id.exists' => 'Rekening tujuan transfer harus berupa akun kas, bank, atau e-wallet aktif.',
            'credit_account_id.required' => 'Kartu kredit atau akun paylater tujuan pembayaran wajib dipilih.',
            'credit_account_id.exists' => 'Akun kredit tujuan tidak valid atau telah diarsipkan.',
            'category_id.required' => 'Kategori pengeluaran wajib dipilih.',
            'category_id.exists' => 'Kategori pengeluaran tidak valid atau telah diarsipkan.',
            'member_id.exists' => 'Member yang dipilih tidak valid atau telah diarsipkan.',
            'frequency.required' => 'Frekuensi perulangan wajib dipilih.',
            'start_date.required' => 'Tanggal mulai jadwal wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'amount.required' => 'Nominal transaksi wajib diisi kecuali dipilih nominal fleksibel.',
            'amount.min' => 'Nominal transaksi minimal Rp 1.',
        ];
    }
}
