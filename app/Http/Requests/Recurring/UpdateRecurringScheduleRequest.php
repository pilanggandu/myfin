<?php

namespace App\Http\Requests\Recurring;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('recurring'));
    }

    public function rules(): array
    {
        $schedule = $this->route('recurring');
        $spaceId = $schedule?->space_id ?? app('currentSpace')?->id;
        $type = $schedule ? $schedule->type : $this->input('type', 'expense');
        $isVariable = $this->boolean('is_variable_amount');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where('space_id', $spaceId)
                    ->where(function ($q) use ($schedule) {
                        $q->whereNull('archived_at');
                        if ($schedule?->member_id) {
                            $q->orWhere('id', $schedule->member_id);
                        }
                    }),
            ],
            'source_account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->where(function ($q) use ($schedule) {
                        $q->where(function ($sub) {
                            $sub->whereNull('archived_at')
                                ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment']);
                        });
                        if ($schedule?->source_account_id) {
                            $q->orWhere('id', $schedule->source_account_id);
                        }
                    }),
            ],
            'frequency' => ['required', 'string', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'start_date' => ['required', 'date'],
            'next_due_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_variable_amount' => ['nullable', 'boolean'],
            'amount' => [
                Rule::requiredIf(! $isVariable),
                'nullable',
                'integer',
                'min:1',
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(['active', 'paused', 'cancelled', 'archived', 'completed'])],
        ];

        if ($type === 'expense') {
            $rules['category_id'] = [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->where('type', 'expense')
                    ->where(function ($q) use ($schedule) {
                        $q->whereNull('archived_at');
                        if ($schedule?->category_id) {
                            $q->orWhere('id', $schedule->category_id);
                        }
                    }),
            ];
        } elseif ($type === 'transfer') {
            $rules['source_account_id'] = [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->where(function ($q) use ($schedule) {
                        $q->where(function ($sub) {
                            $sub->whereNull('archived_at')
                                ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment']);
                        });
                        if ($schedule?->source_account_id) {
                            $q->orWhere('id', $schedule->source_account_id);
                        }
                    }),
            ];
            $rules['destination_account_id'] = [
                'required',
                'integer',
                'different:source_account_id',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->where(function ($q) use ($schedule) {
                        $q->where(function ($sub) {
                            $sub->whereNull('archived_at')
                                ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment']);
                        });
                        if ($schedule?->destination_account_id) {
                            $q->orWhere('id', $schedule->destination_account_id);
                        }
                    }),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama jadwal tagihan atau transfer wajib diisi.',
            'source_account_id.required' => 'Rekening sumber wajib dipilih.',
            'source_account_id.exists' => 'Rekening sumber harus berupa akun kas, bank, atau e-wallet aktif. Untuk Kartu Kredit/Paylater, gunakan menu Kredit & Cicilan.',
            'destination_account_id.required' => 'Rekening tujuan transfer wajib dipilih.',
            'destination_account_id.different' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
            'destination_account_id.exists' => 'Rekening tujuan transfer harus berupa akun kas, bank, atau e-wallet aktif.',
            'category_id.required' => 'Kategori pengeluaran wajib dipilih.',
            'category_id.exists' => 'Kategori pengeluaran tidak valid atau telah diarsipkan.',
            'member_id.exists' => 'Member yang dipilih tidak valid atau telah diarsipkan.',
            'frequency.required' => 'Frekuensi perulangan wajib dipilih.',
            'start_date.required' => 'Tanggal mulai jadwal wajib diisi.',
            'next_due_date.after_or_equal' => 'Tanggal jatuh tempo berikutnya tidak boleh sebelum tanggal mulai.',
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'amount.required' => 'Nominal transaksi wajib diisi kecuali dipilih nominal fleksibel.',
            'amount.min' => 'Nominal transaksi minimal Rp 1.',
        ];
    }
}
