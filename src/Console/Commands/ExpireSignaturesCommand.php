<?php

namespace OiLab\OiLaravelSign\Console\Commands;

use Illuminate\Console\Command;
use OiLab\OiLaravelSign\Actions\ExpireSignature;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;

class ExpireSignaturesCommand extends Command
{
    protected $signature = 'sign:expire';

    protected $description = 'Expire the open signature requests past their date or whose document changed';

    public function handle(): int
    {
        $expired = 0;

        OiLaravelSign::signatureRequestModel()::query()
            ->open()
            ->with('signable')
            ->chunkById(200, function ($requests) use (&$expired): void {
                /** @var SignatureRequest $request */
                foreach ($requests as $request) {
                    if (ExpireSignature::ifStale($request)) {
                        $expired++;
                    }
                }
            });

        $this->components->info("{$expired} signature request(s) expired.");

        return self::SUCCESS;
    }
}
