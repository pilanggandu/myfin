<?php

namespace App\Http\Requests\Reminder;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'max:25'],
            'whatsapp_api_key' => ['nullable', 'string', 'max:100'],
            'whatsapp_gateway_url' => ['nullable', 'url', 'max:255'],
            'email_notifications_enabled' => ['nullable', 'boolean'],
            'whatsapp_notifications_enabled' => ['nullable', 'boolean'],
            'daily_reminder_enabled' => ['nullable', 'boolean'],
            'daily_reminder_time' => ['nullable', 'string', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.max' => 'Nomor WhatsApp maksimal 25 karakter.',
            'whatsapp_api_key.max' => 'API Key maksimal 100 karakter.',
            'whatsapp_gateway_url.url' => 'Format URL gateway tidak valid.',
            'whatsapp_gateway_url.max' => 'URL gateway maksimal 255 karakter.',
        ];
    }
}
