<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;

class StoreTableRequest extends FormRequest
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
            'table_number' => ['required', 'string', 'max:50', 'unique:restaurant_tables,table_number'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
