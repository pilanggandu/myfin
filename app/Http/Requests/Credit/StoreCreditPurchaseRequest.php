<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'credit_account_id' => ['required', 'exists:credit_accounts,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'member_id' => ['nullable', 'exists:members,id'],
            'name' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'integer', 'min:1'],
            'tenor' => ['required', 'integer', 'between:1,60'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'admin_fee' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['required', 'date'],
            'first_due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'credit_account_id.required' => 'Pilih kartu kredit atau paylater yang digunakan.',
            'credit_account_id.exists' => 'Akun kredit tidak ditemukan.',
            'category_id.required' => 'Pilih kategori pengeluaran belanja.',
            'category_id.exists' => 'Kategori pengeluaran tidak valid.',
            'name.required' => 'Nama barang atau deskripsi belanja wajib diisi.',
            'total_amount.required' => 'Nominal belanja wajib diisi.',
            'total_amount.integer' => 'Nominal belanja harus berupa angka bulat dalam Rupiah.',
            'total_amount.min' => 'Nominal belanja minimal Rp 1.',
            'tenor.required' => 'Pilih tenor cicilan.',
            'tenor.between' => 'Tenor cicilan harus antara 1 sampai 60 bulan.',
            'start_date.required' => 'Tanggal transaksi pembelian wajib diisi.',
            'start_date.date' => 'Format tanggal pembelian tidak valid.',
        ];
    }
}
