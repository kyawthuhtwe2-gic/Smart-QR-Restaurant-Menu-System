<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('table_number'))) {
            $this->merge(['table_number' => trim($this->input('table_number'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'table_number' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('restaurant_tables', 'table_number')->ignore($this->route('table'))],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
