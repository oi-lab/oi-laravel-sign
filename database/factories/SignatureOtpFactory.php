<?php

namespace OiLab\OiLaravelSign\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Models\SignatureOtp;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * @extends Factory<SignatureOtp>
 */
class SignatureOtpFactory extends Factory
{
    protected $model = SignatureOtp::class;

    public function definition(): array
    {
        return [
            'signature_request_id' => SignatureRequest::factory(),
            'code_hash' => SignatureOtp::hashCode('123456'),
            'channel' => OtpChannelType::Mail,
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function code(string $code): static
    {
        return $this->state(['code_hash' => SignatureOtp::hashCode($code)]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subMinute()]);
    }
}
