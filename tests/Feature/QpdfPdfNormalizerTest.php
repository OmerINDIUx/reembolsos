<?php

namespace Tests\Feature;

use App\Exceptions\PdfNormalizationException;
use App\Services\QpdfPdfNormalizer;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class QpdfPdfNormalizerTest extends TestCase
{
    public function test_it_normalizes_with_safe_process_arguments(): void
    {
        config([
            'pdf.qpdf.binary' => 'custom-qpdf',
            'pdf.qpdf.timeout' => 25,
        ]);

        $sourcePath = storage_path('app/test source.pdf');
        file_put_contents($sourcePath, '%PDF-1.5 test');

        Process::fake(function (PendingProcess $process) {
            $this->assertIsArray($process->command);
            $this->assertSame('custom-qpdf', $process->command[0]);
            $this->assertSame('--object-streams=disable', $process->command[1]);
            $this->assertSame(25, $process->timeout);

            copy($process->command[2], $process->command[3]);

            return Process::result();
        });

        $normalizedPath = app(QpdfPdfNormalizer::class)->normalize($sourcePath);

        try {
            $this->assertFileExists($normalizedPath);
            $this->assertSame('%PDF-1.5 test', file_get_contents($normalizedPath));
            Process::assertRanTimes(fn (PendingProcess $process) => $process->command[0] === 'custom-qpdf');
        } finally {
            @unlink($sourcePath);
            @unlink($normalizedPath);
        }
    }

    public function test_it_reports_a_failed_qpdf_process(): void
    {
        $sourcePath = storage_path('app/test-failure.pdf');
        file_put_contents($sourcePath, '%PDF-1.5 test');

        Process::fake([
            '*' => Process::result(errorOutput: 'qpdf failed', exitCode: 2),
        ]);

        try {
            $this->expectException(PdfNormalizationException::class);
            $this->expectExceptionMessage('qpdf no pudo convertir');

            app(QpdfPdfNormalizer::class)->normalize($sourcePath);
        } finally {
            @unlink($sourcePath);
        }
    }

    public function test_it_rejects_a_successful_process_without_an_output_file(): void
    {
        $sourcePath = storage_path('app/test-no-output.pdf');
        file_put_contents($sourcePath, '%PDF-1.5 test');

        Process::fake([
            '*' => Process::result(),
        ]);

        try {
            $this->expectException(PdfNormalizationException::class);
            $this->expectExceptionMessage('sin generar un PDF');

            app(QpdfPdfNormalizer::class)->normalize($sourcePath);
        } finally {
            @unlink($sourcePath);
        }
    }

    public function test_it_accepts_an_output_created_with_recoverable_warnings(): void
    {
        $sourcePath = storage_path('app/test-warning.pdf');
        file_put_contents($sourcePath, '%PDF-1.5 warning test');

        Process::fake(function (PendingProcess $process) {
            copy($process->command[2], $process->command[3]);

            return Process::result(errorOutput: 'recoverable warning', exitCode: 3);
        });

        $normalizedPath = app(QpdfPdfNormalizer::class)->normalize($sourcePath);

        try {
            $this->assertFileExists($normalizedPath);
        } finally {
            @unlink($sourcePath);
            @unlink($normalizedPath);
        }
    }
}
