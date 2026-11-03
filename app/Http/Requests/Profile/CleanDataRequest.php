<?php

namespace App\Http\Requests\Profile;

use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;

class CleanDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        $space = Space::find($this->input('space_id'));
        if (! $space) {
            return false;
        }

        return (int) $space->owner_id === (int) $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'space_id' => ['required', 'integer', 'exists:spaces,id'],
            'password' => ['required', 'string', 'current_password'],
            'all' => ['nullable', 'boolean'],
            'transactions' => ['nullable', 'boolean'],
            'budgets' => ['nullable', 'boolean'],
            'recurring' => ['nullable', 'boolean'],
            'members' => ['nullable', 'boolean'],
            'accounts' => ['nullable', 'boolean'],
            'reset_account_balances' => ['nullable', 'boolean'],
            'reset_categories' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'space_id.required' => 'Pilih ruang keuangan yang ingin dibersihkan.',
            'space_id.exists' => 'Ruang keuangan tidak ditemukan.',
            'password.required' => 'Konfirmasi kata sandi Anda diperlukan untuk melanjutkan tindakan ini.',
            'password.current_password' => 'Kata sandi yang Anda masukkan salah.',
        ];
    }
}
