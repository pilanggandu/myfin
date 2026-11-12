<?php

namespace App\Http\Requests;

use App\Models\FinancialTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GlobalSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'scope' => ['nullable', 'string', Rule::in(['all', 'transactions', 'accounts', 'plans', 'other', 'pages'])],
            'type' => ['nullable', 'string', Rule::in(['income', 'expense', 'transfer', 'credit_payment'])],
            'status' => ['nullable', 'string', Rule::in(['posted', 'void', 'all'])],
            'account_id' => ['nullable', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'member_id' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'include_future' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'string', Rule::in(['date_desc', 'date_asc', 'amount_desc', 'amount_asc'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        $cleanAmount = static function (mixed $value): ?string {
            if ($value === null || trim((string) $value) === '') {
                return null;
            }

            $trimmed = trim((string) $value);
            if ($trimmed === '0') {
                return '0';
            }

            $parsed = FinancialTransaction::parseSearchableAmount($trimmed);

            return $parsed !== null ? (string) $parsed : $trimmed;
        };

        $this->merge([
            'q' => $this->has('q') ? trim((string) $this->input('q')) : null,
            'min_amount' => $cleanAmount($this->input('min_amount')),
            'max_amount' => $cleanAmount($this->input('max_amount')),
        ]);
    }
}
