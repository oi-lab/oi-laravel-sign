<?php

namespace OiLab\OiLaravelSign\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use OiLab\OiLaravelSign\Concerns\HasSignatureRequests;
use OiLab\OiLaravelSign\Contracts\Signable;
use OiLab\OiLaravelSign\Data\BrandingData;
use OiLab\OiLaravelSign\Data\SignableAttachmentData;
use TCPDF;

/**
 * @property int $id
 * @property string $title
 * @property int $version
 * @property string $status
 */
class Document extends Model implements Signable
{
    use HasSignatureRequests;

    protected $table = 'documents';

    /** @var list<string> */
    protected $fillable = ['title', 'version', 'status', 'with_attachments'];

    /** @var array<string, mixed> */
    protected $attributes = ['version' => 1, 'status' => 'sent', 'with_attachments' => true];

    public function signableTitle(): string
    {
        return $this->title;
    }

    public function signablePdf(): string
    {
        return static::pdf($this->title.' — v'.$this->version);
    }

    public function signableAttachments(): array
    {
        if (! $this->with_attachments) {
            return [];
        }

        return [new SignableAttachmentData(name: 'CGV 2026.pdf', contents: static::pdf('Conditions générales'))];
    }

    public function canBeSigned(): bool
    {
        return in_array($this->status, ['sent'], true);
    }

    public function signableVersion(): ?string
    {
        return (string) $this->version;
    }

    public function signableBranding(): BrandingData
    {
        return new BrandingData(name: 'Acme SAS', color: '#0f766e');
    }

    public static function pdf(string $text, int $pages = 1): string
    {
        $pdf = new TCPDF;
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        for ($page = 1; $page <= $pages; $page++) {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Write(0, $text);
        }

        return $pdf->Output('', 'S');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['version' => 'integer', 'with_attachments' => 'boolean'];
    }
}
