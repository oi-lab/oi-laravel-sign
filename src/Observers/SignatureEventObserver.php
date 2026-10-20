<?php

namespace OiLab\OiLaravelSign\Observers;

use LogicException;
use OiLab\OiLaravelSign\Models\SignatureEvent;

/**
 * Keeps the evidence trail append only.
 */
class SignatureEventObserver
{
    public function updating(SignatureEvent $event): never
    {
        throw new LogicException('Signature events are append only.');
    }

    public function deleting(SignatureEvent $event): never
    {
        throw new LogicException('Signature events are append only.');
    }
}
