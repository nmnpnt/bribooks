<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->user()->isAuthor();
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'genre'       => ['nullable', 'string', 'max:100'],
            'language'    => ['nullable', 'string', 'size:2'],
        ];
    }
}
