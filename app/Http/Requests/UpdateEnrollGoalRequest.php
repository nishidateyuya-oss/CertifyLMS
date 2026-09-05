<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEnrollGoalRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:100'],
            'target_date' => ['required', 'date', 'after_or_equal:today'],
            'description' => ['nullable', 'string', 'max:1000'], 
        ];
    }

    public function messages(): array{
        return [
            'title.required' => '目標を入力してください',
            'title.max' => '目標は100文字以内で入力してください',
            'target_date.required' => '目標期日を入力してください',
            'target_date.after_or_equal' => '目標期日には今日以降の日付を指定してください。',
            'description.max' => '説明文は1000文字以内で入力してください',
        ];
    }
}
