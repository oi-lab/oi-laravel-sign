<?php

namespace OiLab\OiLaravelSign\Database\Factories;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use OiLab\OiLaravelSign\Enums\OtpChannelType;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * Rows only: no file is written. Use RequestSignature for a real request.
 *
 * @extends Factory<SignatureRequest>
 */
class SignatureRequestFactory extends Factory
{
    protected $model = SignatureRequest::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'signable_type' => 'signable',
            'signable_id' => 1,
            'signer_name' => $this->faker->name(),
            'signer_email' => $this->faker->safeEmail(),
            'signer_phone' => null,
            'signer_role' => null,
            'token_hash' => SignatureRequest::hashToken(Str::random(64)),
            'status' => SignatureStatus::Pending,
            'document_path' => 'signatures/'.$uuid.'/document.pdf',
            'document_hash' => hash('sha256', $uuid),
            'document_version' => '1',
            'otp_channel' => OtpChannelType::Mail,
            'expires_at' => now()->addDays(30),
            'props' => ['title' => $this->faker->sentence(3)],
        ];
    }

    public function withToken(string $token): static
    {
        return $this->state(['token_hash' => SignatureRequest::hashToken($token)]);
    }

    public function viewed(): static
    {
        return $this->state(['status' => SignatureStatus::Viewed, 'viewed_at' => now()]);
    }

    public function signed(): static
    {
        return $this->state(['status' => SignatureStatus::Signed, 'viewed_at' => now(), 'signed_at' => now()]);
    }

    public function expiredAt(DateTimeInterface $date): static
    {
        return $this->state(['expires_at' => $date]);
    }
}
