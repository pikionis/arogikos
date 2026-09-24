<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-content');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $eventId = $this->route('event')?->getKey();

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable', 'string', 'max:200',
                'regex:/^[\pL\pN]+(?:-[\pL\pN]+)*$/u',
                Rule::unique('events', 'slug')->ignore($eventId),
            ],
            'starts_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:200'],
            'body' => ['nullable', 'string', 'max:500000'],
            'is_published' => ['boolean'],
            'cover' => [
                'nullable', 'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
                'dimensions:max_width=5000,max_height=5000',
            ],
            'remove_cover' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'remove_cover' => $this->boolean('remove_cover'),
            'body' => HtmlSanitizer::clean($this->input('body')),
        ]);
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'slug.regex' => 'Το slug επιτρέπεται να έχει μόνο γράμματα, αριθμούς και παύλες.',
            'cover.dimensions' => 'Η εικόνα είναι πολύ μεγάλη σε διαστάσεις (μέγιστο 5000×5000).',
            'cover.max' => 'Η εικόνα δεν πρέπει να ξεπερνά τα 4 MB.',
        ];
    }
}
