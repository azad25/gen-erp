<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:csv,xlsx,xls,txt,docx|max:10240',
            'entity_type' => 'required|in:products,customers,suppliers,employees,opening_stock',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.mimes' => 'The file must be one of: CSV, Excel (.xlsx, .xls), TXT, or DOCX.',
            'file.max' => 'The file may not be larger than 10MB.',
            'entity_type.required' => 'Please select what you want to import.',
            'entity_type.in' => 'Invalid entity type.',
        ];
    }
}
