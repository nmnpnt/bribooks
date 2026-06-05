<?php

namespace App\Http\Requests\Page;

use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'content'      => ['required', 'string'],
            'content_type' => ['nullable', 'in:html,text,markdown'],
            'page_number'  => ['nullable', 'integer', 'min:1'],
        ];
    }
}
