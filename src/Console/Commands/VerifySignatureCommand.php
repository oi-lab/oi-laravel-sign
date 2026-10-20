<?php

namespace OiLab\OiLaravelSign\Console\Commands;

use Illuminate\Console\Command;
use OiLab\OiLaravelSign\Actions\VerifySignedDocument;
use OiLab\OiLaravelSign\Models\SignatureRequest;
use OiLab\OiLaravelSign\OiLaravelSign;

class VerifySignatureCommand extends Command
{
    protected $signature = 'sign:verify
        {request : The UUID of the signature request}
        {--file= : Check this PDF instead of the stored signed PDF}';

    protected $description = 'Check that a signed PDF is intact and its seal valid';

    public function handle(): int
    {
        /** @var SignatureRequest|null $request */
        $request = OiLaravelSign::signatureRequestModel()::withTrashed()->where('uuid', $this->argument('request'))->first();

        if ($request === null || ! $request->isSigned()) {
            $this->components->error('No signed request has this UUID.');

            return self::FAILURE;
        }

        $file = $this->option('file');
        $pdf = $file === null ? null : @file_get_contents($file);

        if ($file !== null && $pdf === false) {
            $this->components->error("Cannot read {$file}.");

            return self::FAILURE;
        }

        $result = VerifySignedDocument::handle($request, $pdf ?: null);

        $this->components->twoColumnDetail('Intact (SHA-256)', $result->intact ? 'yes' : 'no');
        $this->components->twoColumnDetail('Sealed', $result->sealed ? 'yes' : 'no');
        $this->components->twoColumnDetail('Seal valid', $result->sealed ? ($result->seal_valid ? 'yes' : 'no') : '—');

        if ($result->signer_certificate) {
            $this->components->twoColumnDetail('Seal certificate', $result->signer_certificate);
        }

        if ($result->valid) {
            $this->components->info('The signed document is valid.');

            return self::SUCCESS;
        }

        $this->components->error($result->error ?? 'The signed document is not valid.');

        return self::FAILURE;
    }
}
