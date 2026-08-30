<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQaThreadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        return $thread && ($this->user()?->can('update', $thread) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは200文字以内で入力してください',
            'body.required' => '本文を入力してください',
            'body.max' => '本文は5000文字以内で入力してください',
        ];
    }
}
