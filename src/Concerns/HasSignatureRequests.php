<?php

namespace OiLab\OiLaravelSign\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use OiLab\OiLaravelSign\Actions\RequestSignature;
use OiLab\OiLaravelSign\Data\SignatureRequestData;
use OiLab\OiLaravelSign\Data\SignerData;
use OiLab\OiLaravelSign\Enums\SignatureStatus;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;

/**
 * Lets a Signable model be sent for signature and know its signatures.
 *
 * @mixin Model
 */
trait HasSignatureRequests
{
    /**
     * @return MorphMany<SignatureRequest, $this>
     */
    public function signatureRequests(): MorphMany
    {
        return $this->morphMany(OiLaravelSign::signatureRequestModel(), 'signable');
    }

    /**
     * The most recent request, for eager loading.
     *
     * @return MorphOne<SignatureRequest, $this>
     */
    public function latestSignatureRequest(): MorphOne
    {
        return $this->morphOne(OiLaravelSign::signatureRequestModel(), 'signable')->latestOfMany();
    }

    /**
     * @param  array<string, mixed>  $options  See RequestSignature::handle()
     */
    public function requestSignature(SignerData $signer, array $options = []): SignatureRequestData
    {
        return RequestSignature::handle($this, $signer, $options);
    }

    public function latestSignature(): ?SignatureRequest
    {
        return $this->relationLoaded('latestSignatureRequest')
            ? $this->getRelation('latestSignatureRequest')
            : $this->latestSignatureRequest()->first();
    }

    public function isSigned(): bool
    {
        return $this->signatureRequests()->where('status', SignatureStatus::Signed)->exists();
    }
}
