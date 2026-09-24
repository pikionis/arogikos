<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'όνομα',
            'email' => 'email',
            'phone' => 'τηλέφωνο',
            'message' => 'μήνυμα',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Παρακαλούμε συμπληρώστε το όνομά σας.',
            'email.required' => 'Παρακαλούμε συμπληρώστε το email σας.',
            'email.email' => 'Το email δεν φαίνεται έγκυρο.',
            'message.required' => 'Γράψτε μας λίγα λόγια στο μήνυμα.',
            'message.min' => 'Το μήνυμα είναι λίγο σύντομο — πείτε μας λίγα περισσότερα.',
        ];
    }

    /** Whether the anti-spam honeypot field was filled (i.e. a bot). */
    public function isSpam(): bool
    {
        return filled($this->input('company'));
    }
}
