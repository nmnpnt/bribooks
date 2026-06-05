<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class RejectBookRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
