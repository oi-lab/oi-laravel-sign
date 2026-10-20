<?php

namespace OiLab\OiLaravelSign\Console\Commands;

use Illuminate\Console\Command;
use OiLab\OiLaravelSign\Pdf\SealCertificateGenerator;

class MakeCertificateCommand extends Command
{
    protected $signature = 'sign:make-certificate
        {--path= : Directory of the certificate (default: storage/app/oi-sign)}
        {--name= : Common name of the certificate (default: the app name)}
        {--days=3650 : Validity in days}
        {--force : Overwrite an existing certificate}';

    protected $description = 'Create a self-signed seal certificate for development';

    public function handle(SealCertificateGenerator $generator): int
    {
        $directory = $this->option('path') ?: storage_path('app/oi-sign');

        if (file_exists(rtrim($directory, '/').'/seal-certificate.pem') && ! $this->option('force')) {
            $this->components->error('A certificate already exists there. Use --force to replace it.');

            return self::FAILURE;
        }

        $name = (string) ($this->option('name') ?: config('app.name', 'Laravel'));
        $paths = $generator->write($directory, $name.' e-signature seal', $name, (int) $this->option('days'));

        $this->components->info('Self-signed seal certificate created. Add to your .env:');
        $this->line('OI_SIGN_CERTIFICATE_PATH='.$paths['certificate']);
        $this->line('OI_SIGN_PRIVATE_KEY_PATH='.$paths['private_key']);
        $this->components->warn('PDF readers flag a self-signed seal as untrusted: use a certificate from a trusted authority in production.');

        return self::SUCCESS;
    }
}
