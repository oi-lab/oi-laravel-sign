<?php

namespace OiLab\OiLaravelSign\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The signer's refusal, with an optional reason.
 */
class DeclineRequest extends FormRequest
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
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => __('oi-laravel-sign::sign.form.decline_reason'),
        ];
    }
}
