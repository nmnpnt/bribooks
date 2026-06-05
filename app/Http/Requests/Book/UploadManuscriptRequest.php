<?php

namespace App\Http\Requests\Book;

use Illuminate\Foundation\Http\FormRequest;

class UploadManuscriptRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'manuscript' => [
                'required',
                'file',
                'mimes:doc,docx',
                'max:20480', // 20 MB
            ],
        ];
    }
}
