<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class AuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Same door as the screen itself: this form decides who appears on the
        // public team page and which account owns a profile, neither of which
        // belongs to an editor.
        return Gate::allows('manage-users');
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'role_title' => ['nullable', 'string', 'max:120'],
            // Plain text: the About page renders the bio escaped.
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['boolean'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', $this->accountRule()],
            'photo' => [
                'nullable', 'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096',
                'dimensions:max_width=5000,max_height=5000',
            ],
            'remove_photo' => ['boolean'],
            // The chosen rectangle per frame, in the picture's own
            // pixels. Checked against the real file in PhotoCrop.
            'circle_x' => ['nullable', 'integer', 'min:0'],
            'circle_y' => ['nullable', 'integer', 'min:0'],
            'circle_w' => ['nullable', 'integer', 'min:0'],
            'circle_h' => ['nullable', 'integer', 'min:0'],
            'arch_x' => ['nullable', 'integer', 'min:0'],
            'arch_y' => ['nullable', 'integer', 'min:0'],
            'arch_w' => ['nullable', 'integer', 'min:0'],
            'arch_h' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'user_id.unique' => 'Ο λογαριασμός ανήκει ήδη σε άλλο προφίλ συντάκτη.',
        ];
    }

    /**
     * The account must not already belong to a different profile.
     *
     * authors.user_id is unique, so without this the form would take a filled
     * page and answer it with a database error.
     */
    private function accountRule(): Unique
    {
        $unique = Rule::unique('authors', 'user_id');

        $author = $this->route('author');

        return $author ? $unique->ignore($author->getKey()) : $unique;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_photo' => $this->boolean('remove_photo'),
        ]);
    }
}
