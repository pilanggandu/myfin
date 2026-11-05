<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('account'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:bank,e-wallet,cash,investment'],
            'opening_balance' => ['required', 'integer'],
            'opened_on' => ['required', 'date'],
            'is_default' => ['nullable', 'boolean'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama rekening wajib diisi.',
            'type.required' => 'Tipe rekening wajib dipilih.',
            'type.in' => 'Tipe rekening harus berupa bank, e-wallet, kas tunai, atau investasi.',
            'opening_balance.required' => 'Saldo awal wajib diisi.',
            'opening_balance.integer' => 'Saldo awal harus berupa angka bulat dalam rupiah.',
            'opened_on.required' => 'Tanggal buka rekening wajib diisi.',
            'opened_on.date' => 'Format tanggal buka rekening tidak valid.',
        ];
    }
}
