<?php

namespace OiLab\OiLaravelSign\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The one-time code typed by the signer.
 */
class SignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $length = (int) config('oi-laravel-sign.otp.length', 6);

        return [
            'code' => ['required', 'string', 'regex:/^\s*\d{'.$length.'}\s*$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => __('oi-laravel-sign::sign.validation.code'),
            'code.regex' => __('oi-laravel-sign::sign.validation.code'),
        ];
    }
}
