<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSettingImageRequest extends FormRequest
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
        return [
            'avatar' => ['required', 'image', 'mimes:png,jpg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.image' => '画像ファイルを選択してください。',
            'avatar.mimes' => '画像形式は PNG、JPG、WebP のみ対応しています。',
            'avatar.max' => '画像サイズは 2MB 以下にしてください。',
        ];
    }
}
