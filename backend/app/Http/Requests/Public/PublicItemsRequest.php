<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class PublicItemsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'is_featured' => ['nullable', 'in:0,1,true,false'],
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
        $filters['is_featured'] = $this->filled('is_featured') ? $this->boolean('is_featured') : null;

        return $filters;
    }
}
