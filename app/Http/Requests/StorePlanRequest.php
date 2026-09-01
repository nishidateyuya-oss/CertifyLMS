<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'duration_days' => ['required', 'integer', 'between:1,3650'],
            'default_meeting_quota' => ['required', 'integer', 'between:0,1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'プラン名を入力してください',
            'name.max' => 'プラン名は100文字以内で入力してください',
            'description.max' => '説明は2000文字以内で入力してください',
            'duration_days.required' => '受講期間を入力してください',
            'duration_days.between' => '1から3650の範囲で入力してください',
            'default_meeting_quota.required' => '面談回数を入力してください',
            'default_meeting_quota.between' => '0から1000の範囲で入力してください',
            'sort_order' => '0以上の数字で入力してください',
        ];
    }
}
