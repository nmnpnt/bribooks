<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'content'      => ['sometimes', 'string'],
            'content_type' => ['sometimes', 'in:html,text,markdown'],
            'page_number'  => ['sometimes', 'integer', 'min:1'],
        ];
    }
}
