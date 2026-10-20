<?php

namespace OiLab\OiLaravelSign\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The identity the signer declares and their consent, before a code is sent.
 */
class RequestOtpRequest extends FormRequest
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
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:150'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => __('oi-laravel-sign::sign.validation.consent'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => __('oi-laravel-sign::sign.form.first_name'),
            'last_name' => __('oi-laravel-sign::sign.form.last_name'),
            'role' => __('oi-laravel-sign::sign.form.role'),
        ];
    }
}
