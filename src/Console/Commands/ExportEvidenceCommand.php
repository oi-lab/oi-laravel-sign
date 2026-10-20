<?php

namespace OiLab\OiLaravelSign\Console\Commands;

use Illuminate\Console\Command;
use OiLab\OiLaravelSign\OiLaravelSign;

class ExportEvidenceCommand extends Command
{
    protected $signature = 'sign:evidence
        {request : The UUID of the signature request}
        {--output= : Write the JSON to this file instead of the console}';

    protected $description = 'Export the evidence file of a signature request as JSON';

    public function handle(): int
    {
        $request = OiLaravelSign::signatureRequestModel()::withTrashed()->where('uuid', $this->argument('request'))->first();

        if ($request === null) {
            $this->components->error('No signature request has this UUID.');

            return self::FAILURE;
        }

        $json = json_encode($request->evidence()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($output = $this->option('output')) {
            file_put_contents($output, $json.PHP_EOL);
            $this->components->info("Evidence written to {$output}.");

            return self::SUCCESS;
        }

        $this->line($json);

        return self::SUCCESS;
    }
}
