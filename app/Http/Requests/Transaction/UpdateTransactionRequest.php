<?php

namespace App\Http\Requests\Transaction;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('transaction'));
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('amount')) {
            $this->merge(['amount' => $this->normalizeMonetaryInput($this->input('amount'))]);
        }
        if ($this->has('fee_amount')) {
            $this->merge(['fee_amount' => $this->normalizeMonetaryInput($this->input('fee_amount'))]);
        }
        if ($this->has('payments') && is_array($this->input('payments'))) {
            $payments = $this->input('payments');
            foreach ($payments as $key => $payment) {
                if (isset($payment['amount'])) {
                    $payments[$key]['amount'] = $this->normalizeMonetaryInput($payment['amount']);
                }
            }
            $this->merge(['payments' => $payments]);
        }
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
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
        ];

        $isSplitPayment = $this->boolean('is_split_payment');
        $rules['is_split_payment'] = ['nullable', 'boolean'];

        if ($type === 'income' || $type === 'expense') {
            $rules['account_id'] = [
                $isSplitPayment ? 'nullable' : 'required',
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

            if ($isSplitPayment) {
                $rules['payments'] = ['required', 'array', 'min:2'];
                $rules['payments.*.account_id'] = [
                    'required',
                    'integer',
                    'distinct',
                    Rule::exists('accounts', 'id')
                        ->where('space_id', $spaceId)
                        ->whereNull('archived_at'),
                ];
                $rules['payments.*.amount'] = ['required', 'numeric', 'min:0.01'];
            }
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->boolean('is_split_payment')) {
                $totalAmount = (float) $this->input('amount', 0);
                $payments = $this->input('payments', []);
                if (is_array($payments) && count($payments) >= 2) {
                    $sum = round(array_sum(array_map(fn ($p) => (float) ($p['amount'] ?? 0), $payments)), 2);
                    if (abs($sum - round($totalAmount, 2)) >= 0.01) {
                        $validator->errors()->add('payments', 'Total alokasi rekening pembayaran (Rp '.number_format($sum, 0, ',', '.').') harus tepat sama dengan total nominal transaksi (Rp '.number_format($totalAmount, 0, ',', '.').').');
                    }
                }
            }
        });
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
            'payments.required' => 'Daftar alokasi rekening pembayaran wajib diisi.',
            'payments.array' => 'Format alokasi rekening pembayaran tidak valid.',
            'payments.min' => 'Split payment (multi-rekening) membutuhkan minimal 2 rekening pembayaran.',
            'payments.*.account_id.required' => 'Rekening pada setiap baris pembayaran wajib dipilih.',
            'payments.*.account_id.integer' => 'Rekening yang dipilih tidak valid.',
            'payments.*.account_id.distinct' => 'Rekening pembayaran tidak boleh dipilih lebih dari sekali. Gabungkan ke satu baris.',
            'payments.*.amount.required' => 'Nominal pada setiap baris pembayaran wajib diisi.',
            'payments.*.amount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'payments.*.amount.min' => 'Nominal pembayaran minimal Rp 0,01.',
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
