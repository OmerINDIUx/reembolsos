<?php

namespace Tests\Feature;

use App\Http\Controllers\ReimbursementController;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use ReflectionMethod;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Tests\TestCase;

class ReimbursementPdfFallbackTest extends TestCase
{
    public function test_it_unwraps_an_http_response_before_importing_its_pdf_body(): void
    {
        $sourcePath = storage_path('app/http-response.bin');
        file_put_contents(
            $sourcePath,
            "HTTP/1.0 200 OK\r\nContent-Type: application/pdf\r\n\r\n%PDF-1.4\nsynthetic test"
        );

        $pdf = new FakeAttachmentPdf([1]);

        try {
            $method = new ReflectionMethod(ReimbursementController::class, 'addStoredAttachment');
            $method->invoke(
                new ReimbursementController(),
                $pdf,
                $sourcePath,
                'application/octet-stream',
                'FOLIO: TEST-BIN | COMPROBANTE',
                'ARCHIVO'
            );

            $this->assertCount(1, $pdf->sourcePaths);
            $this->assertNotSame($sourcePath, $pdf->sourcePaths[0]);
            $this->assertFileDoesNotExist($pdf->sourcePaths[0]);
            $this->assertSame(1, $pdf->importedPages);
        } finally {
            @unlink($sourcePath);
        }
    }

    public function test_it_normalizes_and_retries_only_after_a_compressed_xref_error(): void
    {
        $sourcePath = storage_path('app/compressed-source.pdf');
        file_put_contents($sourcePath, '%PDF-1.5 compressed test');

        Process::fake(function (PendingProcess $process) {
            copy($process->command[2], $process->command[3]);

            return Process::result();
        });

        $pdf = new FakeAttachmentPdf([
            new CrossReferenceException(
                'Compressed xref',
                CrossReferenceException::COMPRESSED_XREF
            ),
            1,
        ]);

        try {
            $method = new ReflectionMethod(ReimbursementController::class, 'addPdfAttachment');
            $method->invoke(
                new ReimbursementController(),
                $pdf,
                $sourcePath,
                'FOLIO: TEST-1 | COMPROBANTE',
                'ARCHIVO'
            );

            $this->assertCount(2, $pdf->sourcePaths);
            $this->assertSame($sourcePath, $pdf->sourcePaths[0]);
            $this->assertNotSame($sourcePath, $pdf->sourcePaths[1]);
            $this->assertFileDoesNotExist($pdf->sourcePaths[1]);
            $this->assertSame(1, $pdf->importedPages);
            Process::assertRanTimes(fn (PendingProcess $process) => $process->command[1] === '--object-streams=disable');
        } finally {
            @unlink($sourcePath);
        }
    }

    public function test_it_does_not_run_qpdf_for_an_encrypted_pdf(): void
    {
        $sourcePath = storage_path('app/encrypted-source.pdf');
        file_put_contents($sourcePath, '%PDF-1.7 encrypted test');

        Process::fake();

        $pdf = new FakeAttachmentPdf([
            new CrossReferenceException(
                'Encrypted PDF',
                CrossReferenceException::ENCRYPTED
            ),
        ]);

        try {
            $method = new ReflectionMethod(ReimbursementController::class, 'addPdfAttachment');
            $method->invoke(
                new ReimbursementController(),
                $pdf,
                $sourcePath,
                'FOLIO: TEST-2 | COMPROBANTE',
                'ARCHIVO'
            );

            Process::assertNothingRan();
            $this->assertStringContainsString('PDF PROTEGIDO', $pdf->cells[0]);
        } finally {
            @unlink($sourcePath);
        }
    }
}

class FakeAttachmentPdf
{
    public array $sourcePaths = [];

    public int $importedPages = 0;

    public array $cells = [];

    public function __construct(private array $sourceResults)
    {
    }

    public function setSourceFile(string $path): int
    {
        $this->sourcePaths[] = $path;
        $result = array_shift($this->sourceResults);

        if ($result instanceof \Throwable) {
            throw $result;
        }

        return $result;
    }

    public function importPage(int $page): int
    {
        $this->importedPages++;

        return $page;
    }

    public function addPage(): void
    {
    }

    public function useTemplate(int $pageId): void
    {
    }

    public function SetFillColor(int $red, int $green, int $blue): void
    {
    }

    public function SetTextColor(int $red, int $green, int $blue): void
    {
    }

    public function SetFont(string $family, string $style, int $size): void
    {
    }

    public function SetXY(int $x, int $y): void
    {
    }

    public function Cell(
        int $width,
        int $height,
        string $text,
        int $border = 0,
        int $lineBreak = 0,
        string $align = '',
        bool $fill = false
    ): void {
        $this->cells[] = mb_convert_encoding($text, 'UTF-8', 'Windows-1252');
    }

    public function MultiCell(
        int $width,
        int $height,
        string $text,
        int $border = 0,
        string $align = ''
    ): void {
    }
}
