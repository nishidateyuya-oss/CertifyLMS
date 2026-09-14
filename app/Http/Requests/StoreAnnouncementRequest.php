<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserRole;
use App\Enums\AnnouncementTargetType;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::Admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'target_type' => ['required', Rule::enum(AnnouncementTargetType::class)],
            
            // target_type が certification の場合は必須かつ実在チェック
            'target_certification_id' => [
                'nullable',
                Rule::requiredIf($this->input('target_type') === AnnouncementTargetType::Certification->value),
                'exists:certifications,id',
            ],
            
            // target_type が user の場合は必須かつ実在チェック
            'target_user_id' => [
                'nullable',
                Rule::requiredIf($this->input('target_type') === AnnouncementTargetType::User->value),
                'exists:users,id',
            ],
        ];
    }

    public function  messages(): array {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは200文字以内で入力してください',
            'body.required' => '本文を入力してください',
            'body.max' => '本文は5000文字以内で入力してください',
            'target_type.required' => '配信対象を選択してください',
            'target_type.Illuminate\Validation\Rules\Enum' => '正しい配信対象を選択してください',
            'target_certification_id.required_if' => '対象の資格を選択してください',
            'target_certification_id.exists' => '選択された資格が存在しません',
            'target_user_id.required_if' => '対象の受講生を選択してください',
            'target_user_id.exists' => '選択された受講生が存在しません',
        ];
    }
}
