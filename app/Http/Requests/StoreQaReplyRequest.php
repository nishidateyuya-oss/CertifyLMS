<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\QaReply;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQaReplyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        return $this->user()?->can('create', [QaReply::class, $thread]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => '回答を入力してください',
            'body.max' => '本文は5000文字以内で入力してください',
        ];
    }
}
