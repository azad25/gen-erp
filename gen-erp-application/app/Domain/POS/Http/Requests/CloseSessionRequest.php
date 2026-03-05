<?php

namespace App\Domain\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'closing_cash' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
