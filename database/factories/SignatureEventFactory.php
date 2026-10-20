<?php

namespace OiLab\OiLaravelSign\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use OiLab\OiLaravelSign\Enums\SignatureEventType;
use OiLab\OiLaravelSign\Models\SignatureEvent;
use OiLab\OiLaravelSign\Models\SignatureRequest;

/**
 * @extends Factory<SignatureEvent>
 */
class SignatureEventFactory extends Factory
{
    protected $model = SignatureEvent::class;

    public function definition(): array
    {
        return [
            'signature_request_id' => SignatureRequest::factory(),
            'type' => SignatureEventType::Viewed,
            'ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
        ];
    }
}
