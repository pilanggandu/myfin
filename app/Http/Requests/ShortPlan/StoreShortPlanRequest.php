<?php

namespace App\Http\Requests\ShortPlan;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShortPlanRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $merged = [];

        if ($this->has('planned_amount')) {
            $merged['planned_amount'] = $this->normalizeMonetaryInput($this->input('planned_amount'));
        }

        if (is_array($this->input('items'))) {
            $items = [];
            foreach ($this->input('items') as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $title = trim((string) ($item['title'] ?? ''));
                $amountRaw = $item['estimated_amount'] ?? null;

                if ($title === '' && ($amountRaw === null || $amountRaw === '')) {
                    continue;
                }

                $items[] = [
                    'title' => $title,
                    'estimated_amount' => $this->normalizeMonetaryInput($amountRaw) ?? 0,
                ];
            }
            $merged['items'] = $items;
        }

        if (! empty($merged)) {
            $this->merge($merged);
        }
    }

    public function rules(): array
    {
        $spaceId = app('currentSpace')?->id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'planned_amount' => ['required', 'numeric', 'min:1'],
            'target_date' => ['nullable', 'date'],
            'priority' => ['required', 'in:high,medium,low'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')
                    ->where('space_id', $spaceId)
                    ->whereIn('type', ['bank', 'e-wallet', 'cash', 'investment'])
                    ->whereNull('archived_at'),
            ],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('space_id', $spaceId)
                    ->where('type', 'expense')
                    ->whereNull('archived_at'),
            ],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id')
                    ->where('space_id', $spaceId)
                    ->whereNull('archived_at'),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['nullable', 'array', 'max:30'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.estimated_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Nama rencana wajib diisi.',
            'planned_amount.required' => 'Estimasi biaya wajib diisi.',
            'planned_amount.numeric' => 'Estimasi biaya harus berupa angka yang valid.',
            'planned_amount.min' => 'Estimasi biaya minimal Rp 1.',
            'target_date.date' => 'Format target tanggal tidak valid.',
            'priority.required' => 'Prioritas rencana wajib dipilih.',
            'priority.in' => 'Pilihan prioritas tidak valid.',
            'account_id.exists' => 'Rekening sumber yang dipilih tidak valid atau sudah diarsipkan.',
            'category_id.exists' => 'Kategori pengeluaran yang dipilih tidak valid atau sudah diarsipkan.',
            'member_id.exists' => 'Member yang dipilih tidak valid atau sudah diarsipkan.',
            'items.*.title.required' => 'Nama item rincian wajib diisi.',
            'items.*.estimated_amount.min' => 'Estimasi item rincian tidak boleh negatif.',
        ];
    }
}
