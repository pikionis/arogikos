<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AlbumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-content');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $albumId = $this->route('album')?->getKey();

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable', 'string', 'max:200',
                'regex:/^[\pL\pN]+(?:-[\pL\pN]+)*$/u',
                Rule::unique('albums', 'slug')->ignore($albumId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Το slug επιτρέπεται να έχει μόνο γράμματα, αριθμούς και παύλες.',
        ];
    }
}
