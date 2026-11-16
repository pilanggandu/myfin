<?php

namespace App\Http\Requests\Transaction;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreTransactionRuleRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'stop_processing' => $this->boolean('stop_processing', true),
            'amount_min' => $this->normalizeMonetaryInput($this->input('amount_min')),
            'amount_max' => $this->normalizeMonetaryInput($this->input('amount_max')),
            'priority' => $this->filled('priority') ? (int) $this->input('priority') : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $space = app('currentSpace');
        $spaceId = $space?->id ?? 0;

        return [
            'name' => ['required', 'string', 'max:150'],
            'priority' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'is_active' => ['boolean'],
            'type_criterion' => ['required', 'in:all,expense,income'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'keyword_operator' => ['required', 'in:contains,starts_with,ends_with,exact,regex'],
            'amount_operator' => ['required', 'in:any,equals,greater_than,greater_than_or_equal,less_than,less_than_or_equal,between'],
            'amount_min' => ['nullable', 'numeric', 'min:0'],
            'amount_max' => ['nullable', 'numeric', 'min:0'],
            'account_id' => ['nullable', 'integer', "exists:accounts,id,space_id,{$spaceId}"],
            'action_category_id' => ['nullable', 'integer', "exists:categories,id,space_id,{$spaceId}"],
            'action_member_id' => ['nullable', 'integer', "exists:members,id,space_id,{$spaceId}"],
            'action_description' => ['nullable', 'string', 'max:255'],
            'action_note' => ['nullable', 'string', 'max:1000'],
            'stop_processing' => ['boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            // Setidaknya satu tindakan harus dipilih
            if (
                ! $this->filled('action_category_id') &&
                ! $this->filled('action_member_id') &&
                ! $this->filled('action_description') &&
                ! $this->filled('action_note')
            ) {
                $validator->errors()->add('action_category_id', 'Tentukan setidaknya satu tindakan otomatis (kategori, member, keterangan, atau catatan).');
            }

            // Validasi operator between: amount_max harus >= amount_min
            if ($this->input('amount_operator') === 'between') {
                if ($this->input('amount_min') === null || $this->input('amount_max') === null) {
                    $validator->errors()->add('amount_min', 'Untuk kondisi nominal rentang, batas nominal minimum dan maksimum wajib diisi.');
                } elseif ((float) $this->input('amount_max') < (float) $this->input('amount_min')) {
                    $validator->errors()->add('amount_max', 'Batas nominal maksimum harus lebih besar atau sama dengan nominal minimum.');
                }
            }

            // Validasi operator regex
            if ($this->input('keyword_operator') === 'regex' && $this->filled('keyword')) {
                $pattern = (string) $this->input('keyword');
                if (! str_starts_with($pattern, '/') && ! str_starts_with($pattern, '#') && ! str_starts_with($pattern, '~')) {
                    $pattern = '#'.str_replace('#', '\#', $pattern).'#iu';
                }
                if (@preg_match($pattern, '') === false) {
                    $validator->errors()->add('keyword', 'Format pola RegEx tidak valid.');
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama aturan wajib diisi.',
            'name.max' => 'Nama aturan maksimal 150 karakter.',
            'type_criterion.required' => 'Pilihan jenis transaksi wajib ditentukan.',
            'keyword_operator.required' => 'Operator kata kunci wajib dipilih.',
            'amount_operator.required' => 'Kondisi nominal wajib dipilih.',
        ];
    }
}
