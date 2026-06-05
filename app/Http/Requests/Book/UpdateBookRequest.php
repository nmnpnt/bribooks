<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'genre'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'language'    => ['sometimes', 'nullable', 'string', 'size:2'],
        ];
    }
}
