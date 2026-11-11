<?php

namespace App\Http\Requests\ShortPlan;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShortPlanRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('planned_amount')) {
            $this->merge([
                'planned_amount' => $this->normalizeMonetaryInput($this->input('planned_amount')),
            ]);
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
            'status' => ['nullable', 'in:planned,done,cancelled'],
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
            'status.in' => 'Status rencana tidak valid.',
            'account_id.exists' => 'Rekening sumber yang dipilih tidak valid atau sudah diarsipkan.',
            'category_id.exists' => 'Kategori pengeluaran yang dipilih tidak valid atau sudah diarsipkan.',
            'member_id.exists' => 'Member yang dipilih tidak valid atau sudah diarsipkan.',
        ];
    }
}
