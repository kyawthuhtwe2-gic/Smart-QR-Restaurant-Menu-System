<?php

namespace App\Http\Requests\Table;

use Illuminate\Foundation\Http\FormRequest;

class IndexTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'in:0,1,true,false'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        $filters = $this->validated();
        $filters['is_active'] = $this->filled('is_active') ? $this->boolean('is_active') : null;

        return $filters;
    }
}
