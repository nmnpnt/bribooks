<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class CreateVersionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'label'        => ['nullable', 'string', 'max:100'],
            'change_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
