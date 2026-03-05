<?php

namespace App\Domain\POS\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'branch_id' => 'required|exists:branches,id',
            'opening_cash' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
