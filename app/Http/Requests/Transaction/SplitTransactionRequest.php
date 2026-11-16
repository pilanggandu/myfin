<?php

namespace App\Http\Requests\Transaction;

use App\Http\Requests\Concerns\NormalizesMonetaryInputs;
use Illuminate\Foundation\Http\FormRequest;

class SplitTransactionRequest extends FormRequest
{
    use NormalizesMonetaryInputs;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('splits') && is_array($this->input('splits'))) {
            $splits = $this->input('splits');
            foreach ($splits as $key => $split) {
                if (isset($split['amount'])) {
                    $splits[$key]['amount'] = $this->normalizeMonetaryInput($split['amount']);
                }
            }
            $this->merge(['splits' => $splits]);
        }
    }

    public function rules(): array
    {
        return [
            'splits' => ['required', 'array', 'min:2'],
            'splits.*.category_id' => ['required', 'integer'],
            'splits.*.member_id' => ['nullable', 'integer'],
            'splits.*.amount' => ['required', 'numeric', 'min:0.01'],
            'splits.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'splits.required' => 'Daftar alokasi pemisahan transaksi wajib diisi.',
            'splits.array' => 'Format alokasi pemisahan transaksi tidak valid.',
            'splits.min' => 'Pemisahan transaksi membutuhkan minimal 2 baris alokasi.',
            'splits.*.category_id.required' => 'Kategori pada setiap alokasi wajib dipilih.',
            'splits.*.category_id.integer' => 'Kategori yang dipilih tidak valid.',
            'splits.*.amount.required' => 'Nominal pada setiap alokasi wajib diisi.',
            'splits.*.amount.numeric' => 'Nominal alokasi harus berupa angka.',
            'splits.*.amount.min' => 'Nominal alokasi minimal Rp 0,01.',
            'splits.*.notes.max' => 'Catatan alokasi maksimal 255 karakter.',
        ];
    }
}
