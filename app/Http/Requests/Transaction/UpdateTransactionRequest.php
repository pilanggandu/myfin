<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('transaction'));
    }

    public function rules(): array
    {
        $transaction = $this->route('transaction');
        $spaceId = $transaction?->space_id ?? app('currentSpace')?->id;
        $type = $transaction ? $transaction->type : $this->type;

        $rules = [
            'version' => ['required', 'integer'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'string', 'max:8'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
        ];

        if ($type === 'income' || $type === 'expense') {
            $rules['account_id'] = [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ];
            $rules['category_id'] = [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->where('type', $type)
                    ->whereNull('archived_at'),
            ];
        } elseif ($type === 'transfer') {
            $rules['source_account_id'] = [
                'required',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ];
            $rules['destination_account_id'] = [
                'required',
                'integer',
                'different:source_account_id',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'version.required' => 'Nomor versi transaksi wajib disertakan.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.date' => 'Format tanggal transaksi tidak valid.',
            'description.required' => 'Keterangan transaksi wajib diisi.',
            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.integer' => 'Nominal transaksi harus berupa angka rupiah.',
            'amount.min' => 'Nominal transaksi minimal Rp 1.',
            'account_id.required' => 'Rekening transaksi wajib dipilih.',
            'account_id.exists' => 'Rekening yang dipilih tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'category_id.required' => 'Kategori transaksi wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'source_account_id.required' => 'Rekening asal transfer wajib dipilih.',
            'source_account_id.exists' => 'Rekening asal transfer tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'destination_account_id.required' => 'Rekening tujuan transfer wajib dipilih.',
            'destination_account_id.exists' => 'Rekening tujuan transfer tidak valid, bukan milik ruang aktif, atau telah diarsipkan.',
            'destination_account_id.different' => 'Rekening tujuan transfer harus berbeda dengan rekening asal.',
        ];
    }
}
