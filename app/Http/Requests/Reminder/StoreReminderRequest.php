<?php

namespace App\Http\Requests\Reminder;

use Illuminate\Foundation\Http\FormRequest;

class StoreReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'in:bill_payment,daily_bookkeeping,budget_alert,custom'],
            'channel' => ['required', 'string', 'in:email,whatsapp,both'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'reminder_time' => ['nullable', 'string', 'max:5'],
            'frequency' => ['required', 'string', 'in:once,daily,weekly,monthly'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul pengingat wajib diisi.',
            'title.max' => 'Judul pengingat maksimal 150 karakter.',
            'type.required' => 'Tipe pengingat wajib dipilih.',
            'type.in' => 'Tipe pengingat tidak valid.',
            'channel.required' => 'Saluran notifikasi wajib dipilih.',
            'channel.in' => 'Saluran harus berupa Email, WhatsApp, atau Keduanya.',
            'amount.numeric' => 'Nominal tagihan harus berupa angka.',
            'amount.min' => 'Nominal minimal Rp 0.',
            'due_date.date' => 'Format tanggal jatuh tempo tidak valid.',
            'frequency.required' => 'Frekuensi pengingat wajib dipilih.',
            'frequency.in' => 'Frekuensi tidak valid.',
        ];
    }
}
