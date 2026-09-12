<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The stock Breeze version validated a single `name`, which this app's users
 * table does not have — so the field passed validation and was then dropped
 * silently by fill(), and nobody could ever change their name.
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            // The avatar that shows beside their wishes on a celebration page.
            'photo'        => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:4096'],
            'remove_photo' => ['nullable', 'boolean'],

            'email_notifications_enabled' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo.image' => 'That file is not a picture. JPEG, PNG or WebP.',
            'photo.max'   => 'That picture is over 4MB. Try a smaller one.',
        ];
    }

    /**
     * Accept a single "name" as well, so an older client — or the mobile app
     * posting one field — still updates something rather than nothing.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('name') && ! $this->filled('first_name')) {
            $parts = preg_split('/\s+/', trim($this->input('name')), 2);

            $this->merge([
                'first_name' => $parts[0] ?? '',
                'last_name'  => $parts[1] ?? $this->user()->last_name ?? '',
            ]);
        }
    }
}
