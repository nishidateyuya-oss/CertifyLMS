<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\QaThread;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreQaThreadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', QaThread::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'certification_id' => ['required', 'exists:certifications,id'],
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'certification_id.required' => '資格を選択してください',
            'title.required' => 'タイトルを入力してください',
            'title.max' => 'タイトルは200文字以内で入力してください',
            'body.required' => '本文を入力してください',
            'body.max' => '本文は5000文字以内で入力してください',
        ];
    }
}
