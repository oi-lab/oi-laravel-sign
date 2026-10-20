<?php

namespace OiLab\OiLaravelSign\Pdf;

use OiLab\OiLaravelSign\Data\SignatureCertificateData;
use OiLab\OiLaravelSign\Exceptions\SignatureException;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\StreamReader;
use setasign\Fpdi\Tcpdf\Fpdi;

/**
 * Rebuilds a PDF page by page with FPDI and appends the certificate page
 * with TCPDF, ready to be sealed.
 */
class TcpdfDocumentBuilder
{
    public function __construct(
        protected CertificatePageRenderer $renderer,
    ) {}

    public function build(string $pdf, SignatureCertificateData $certificate): Fpdi
    {
        $document = new Fpdi('P', 'mm', 'A4', true, 'UTF-8');
        $document->setPrintHeader(false);
        $document->setPrintFooter(false);
        $document->SetCreator('oi-lab/oi-laravel-sign');
        $document->SetTitle($certificate->title);
        $document->SetSubject($certificate->transaction_id);
        $document->SetAuthor($certificate->issuer);

        try {
            $pages = $document->setSourceFile(StreamReader::createByString($pdf));

            for ($page = 1; $page <= $pages; $page++) {
                $template = $document->importPage($page);
                $size = $document->getTemplateSize($template);

                $document->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $document->useTemplate($template);
            }
        } catch (CrossReferenceException|PdfParserException $exception) {
            throw SignatureException::unreadablePdf($exception->getMessage());
        }

        $document->SetMargins(15, 15, 15);
        $document->SetAutoPageBreak(true, 15);
        $document->AddPage('P', 'A4');
        $document->SetFont('dejavusans', '', 9);
        $document->writeHTML($this->renderer->render($certificate));

        return $document;
    }
}
