<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:50'],
            'bio' => ['nullable', 'string', 'max:1000'],
        ];

        if ($this->user()->role === UserRole::Coach) {
            $rules['meeting_url'] = ['required', 'string', 'url', 'max:500'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'name' => 'お名前',
            'bio' => '自己紹介',
            'meeting_url' => 'ミーティング URL',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'meeting_url.required' => 'ミーティング URL を入力してください。',
            'meeting_url.url' => 'ミーティング URL を正しい形式で入力してください。',
        ];
    }
}
