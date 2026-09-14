<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\EnrollmentNote;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');

        return $this->user()->can('create', [EnrollmentNote::class, $enrollment]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.required' => '内容を入力してください',
            'body.max' => '2000文字以内で入力してください',
        ];
    }
}
