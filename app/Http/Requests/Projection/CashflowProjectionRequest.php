<?php

namespace App\Http\Requests\Projection;

use Illuminate\Foundation\Http\FormRequest;

class CashflowProjectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $space = app('currentSpace');

        return $space && $space->hasActiveMember($this->user());
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'horizon' => ['nullable', 'integer', 'in:30,60,90,180,365'],
            'account_id' => ['nullable', 'integer'],
            'include_recurring' => ['nullable', 'string', 'in:0,1,true,false'],
            'include_installments' => ['nullable', 'string', 'in:0,1,true,false'],
            'include_planned_incomes' => ['nullable', 'string', 'in:0,1,true,false'],
            'include_short_plans' => ['nullable', 'string', 'in:0,1,true,false'],
            'tab' => ['nullable', 'string', 'in:projection,planned_incomes'],
        ];
    }
}
