<?php

namespace App\Http\Requests\Transaction;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge(['amount' => $this->normalizeMonetaryInput($this->input('amount'))]);
        }
        if ($this->has('fee_amount')) {
            $this->merge(['fee_amount' => $this->normalizeMonetaryInput($this->input('fee_amount'))]);
        }
    }

    public function rules(): array
    {
        $space = app('currentSpace');
        $spaceId = $space?->id;

        $rules = [
            'type' => ['required', 'in:income,expense,transfer'],
            'transaction_date' => ['required', 'date'],
            'transaction_time' => ['nullable', 'string', 'max:8'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'request_key' => ['nullable', 'string', 'max:64'],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
        ];

        if ($this->type === 'income' || $this->type === 'expense') {
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
                    ->where('type', $this->type)
                    ->whereNull('archived_at'),
            ];
        } elseif ($this->type === 'transfer') {
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
            $rules['fee_amount'] = ['nullable', 'numeric', 'min:0'];
            $rules['fee_category_id'] = [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->where('type', 'expense')
                    ->whereNull('archived_at'),
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Jenis transaksi wajib dipilih.',
            'type.in' => 'Jenis transaksi harus berupa pemasukan, pengeluaran, atau transfer.',
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
            'fee_amount.numeric' => 'Biaya admin transfer harus berupa angka.',
            'fee_amount.min' => 'Biaya admin transfer tidak boleh bernilai negatif.',
            'fee_category_id.exists' => 'Kategori biaya admin tidak valid atau telah diarsipkan.',
        ];
    }
}
