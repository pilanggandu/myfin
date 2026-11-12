<?php

namespace App\Http\Requests\Transaction;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkCorrectTransactionsRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    /**
     * Daftar flag field yang dapat dipilih untuk diterapkan secara seragam.
     *
     * @var array<int, string>
     */
    private const APPLY_FLAGS = [
        'apply_category',
        'apply_account',
        'apply_source_account',
        'apply_destination_account',
        'apply_fee_amount',
        'apply_fee_category',
        'apply_member',
        'apply_transaction_date',
        'apply_transaction_time',
        'apply_description',
        'apply_amount',
    ];

    /**
     * Pastikan pengguna saat ini adalah anggota aktif pada space terpilih.
     */
    public function authorize(): bool
    {
        $space = app()->bound('currentSpace') ? app('currentSpace') : null;
        $user = $this->user();

        return $user !== null && $space !== null && $space->hasMember($user);
    }

    /**
     * Normalisasi input numerik, boolean flag, dan string kosong sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $normalized = [];

        foreach (self::APPLY_FLAGS as $flag) {
            $normalized[$flag] = $this->boolean($flag);
        }

        if ($normalized['apply_amount'] && $this->has('amount')) {
            $normalized['amount'] = $this->normalizeMonetaryInput($this->input('amount'));
        } else {
            $normalized['amount'] = null;
        }

        if ($normalized['apply_fee_amount'] && $this->has('fee_amount')) {
            $normalized['fee_amount'] = $this->normalizeMonetaryInput($this->input('fee_amount')) ?? 0;
        } else {
            $normalized['fee_amount'] = null;
        }

        foreach (['category_id', 'account_id', 'source_account_id', 'destination_account_id', 'fee_category_id', 'member_id', 'transaction_date', 'transaction_time', 'description', 'reason'] as $optionalField) {
            if ($this->input($optionalField) === '') {
                $normalized[$optionalField] = null;
            }
        }

        $this->merge($normalized);
    }

    /**
     * Ambil aturan validasi untuk permintaan koreksi atau pembatalan massal transaksi.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $spaceId = app()->bound('currentSpace') ? app('currentSpace')->id : null;
        $isUpdate = $this->input('action') === 'update';

        $destinationRules = [
            Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_destination_account')),
            'nullable',
            'integer',
            Rule::exists('accounts', 'id')
                ->where('space_id', $spaceId)
                ->whereNull('archived_at'),
        ];

        if ($isUpdate && $this->boolean('apply_source_account') && $this->boolean('apply_destination_account')) {
            $destinationRules[] = 'different:source_account_id';
        }

        $rules = [
            'action' => ['required', Rule::in(['update', 'void'])],
            'transaction_ids' => ['required', 'array', 'min:1', 'max:200'],
            'transaction_ids.*' => ['required', 'integer', 'min:1'],
            'versions' => ['nullable', 'array'],
            'versions.*' => ['nullable', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'category_id' => [
                Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_category')),
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
            'account_id' => [
                Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_account')),
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
            'source_account_id' => [
                Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_source_account')),
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
            'destination_account_id' => $destinationRules,
            'fee_amount' => [
                Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_fee_amount')),
                'nullable',
                'numeric',
                'min:0',
            ],
            'fee_category_id' => [
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
            'transaction_date' => [
                Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_transaction_date')),
                'nullable',
                'date',
            ],
            'transaction_time' => [
                'nullable',
                'string',
                'regex:/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/',
            ],
            'description' => [
                Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_description')),
                'nullable',
                'string',
                'max:255',
            ],
            'amount' => [
                Rule::requiredIf(fn (): bool => $isUpdate && $this->boolean('apply_amount')),
                'nullable',
                'numeric',
                'min:0.01',
            ],
        ];

        foreach (self::APPLY_FLAGS as $flag) {
            $rules[$flag] = ['boolean'];
        }

        return $rules;
    }

    /**
     * Validasi tambahan setelah aturan dasar: pastikan minimal satu kolom dipilih saat mode update.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('action') !== 'update') {
                    return;
                }

                $hasAnyFlag = collect(self::APPLY_FLAGS)->contains(
                    fn (string $flag): bool => $this->boolean($flag)
                );

                if (! $hasAnyFlag) {
                    $validator->errors()->add('fields', 'Pilih minimal satu kolom yang ingin dikoreksi secara massal.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Aksi koreksi massal wajib dipilih.',
            'action.in' => 'Aksi koreksi massal tidak valid.',
            'transaction_ids.required' => 'Pilih minimal satu transaksi untuk diproses.',
            'transaction_ids.min' => 'Pilih minimal satu transaksi untuk diproses.',
            'transaction_ids.max' => 'Maksimal 200 transaksi dapat diproses dalam satu kali koreksi massal.',
            'category_id.required' => 'Kategori wajib dipilih jika perubahan kategori diaktifkan.',
            'category_id.exists' => 'Kategori tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'account_id.required' => 'Rekening wajib dipilih jika perubahan rekening diaktifkan.',
            'account_id.exists' => 'Rekening tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'source_account_id.required' => 'Rekening asal transfer wajib dipilih.',
            'source_account_id.exists' => 'Rekening asal transfer tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'destination_account_id.required' => 'Rekening tujuan transfer wajib dipilih.',
            'destination_account_id.exists' => 'Rekening tujuan transfer tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'destination_account_id.different' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
            'fee_amount.required' => 'Biaya admin wajib diisi jika perubahan biaya admin diaktifkan.',
            'fee_amount.numeric' => 'Biaya admin harus berupa angka.',
            'fee_amount.min' => 'Biaya admin tidak boleh bernilai negatif.',
            'fee_category_id.exists' => 'Kategori biaya admin tidak valid atau telah diarsipkan.',
            'member_id.exists' => 'Member tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi jika perubahan tanggal diaktifkan.',
            'transaction_date.date' => 'Format tanggal transaksi tidak valid.',
            'transaction_time.regex' => 'Format jam transaksi harus HH:MM (contoh: 14:30).',
            'description.required' => 'Keterangan transaksi wajib diisi jika perubahan keterangan diaktifkan.',
            'amount.required' => 'Nominal wajib diisi jika perubahan nominal diaktifkan.',
            'amount.numeric' => 'Nominal transaksi harus berupa angka.',
            'amount.min' => 'Nominal transaksi minimal Rp 1.',
        ];
    }
}
