<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Support\Categories;
use App\Support\HtmlSanitizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('manage-content');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $articleId = $this->route('article')?->getKey();

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable', 'string', 'max:200',
                'regex:/^[\pL\pN]+(?:-[\pL\pN]+)*$/u',
                Rule::unique('articles', 'slug')->ignore($articleId),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:500000'],
            // Any author may be picked, including inactive ones: invited panel
            // members start inactive so they do not appear on the About page,
            // but they must still be selectable as a byline.
            'author_ids' => ['array'],
            'author_ids.*' => ['integer', Rule::exists('authors', 'id')],
            // Any existing category, archived included: an old article must
            // keep the ones it was given even after one stopped being offered.
            'category_keys' => ['array'],
            'category_keys.*' => ['string', Rule::in(array_keys(app(Categories::class)->all()))],
            'cover_preset' => ['nullable', 'string', Rule::in(array_keys(config('cms-covers', [])))],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['boolean'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'seo_description' => ['nullable', 'string', 'max:300'],
            'cover' => [
                'nullable', 'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
                // GD holds source + destination bitmaps in memory while making
                // the thumb/web conversions; a 6000px photo can exhaust a
                // shared host's PHP memory limit.
                'dimensions:max_width=5000,max_height=5000',
            ],
            'remove_cover' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // The form carries one empty author_ids[] so that unticking everyone
        // arrives as "nobody" rather than as nothing at all; it is not an id.
        $this->merge([
            'author_ids' => array_values(array_filter(
                (array) $this->input('author_ids', []),
                fn ($id) => filled($id)
            )),
            'category_keys' => array_values(array_unique(array_filter(
                (array) $this->input('category_keys', []),
                fn ($key) => filled($key)
            ))),
        ]);

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
