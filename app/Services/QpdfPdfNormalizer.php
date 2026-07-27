<?php

namespace App\Services;

use App\Exceptions\PdfNormalizationException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Throwable;

class QpdfPdfNormalizer
{
    /**
     * Rewrite a PDF without object streams or compressed cross-references.
     *
     * The caller owns the returned temporary file and must delete it.
     */
    public function normalize(string $sourcePath): string
    {
        if (! is_file($sourcePath) || ! is_readable($sourcePath)) {
            throw new PdfNormalizationException('El archivo PDF de origen no se puede leer.');
        }

        $tempDirectory = storage_path('app/temp');
        File::ensureDirectoryExists($tempDirectory);

        $outputPath = $tempDirectory.DIRECTORY_SEPARATOR.'qpdf_'.Str::uuid().'.pdf';
        $command = [
            (string) config('pdf.qpdf.binary', 'qpdf'),
            '--object-streams=disable',
            $sourcePath,
            $outputPath,
        ];

        try {
            $result = Process::timeout((int) config('pdf.qpdf.timeout', 60))
                ->run($command);

            clearstatcache(true, $outputPath);
            $hasOutput = is_file($outputPath) && filesize($outputPath) > 0;
            $completedWithWarnings = $result->exitCode() === 3 && $hasOutput;

            if ($result->failed() && ! $completedWithWarnings) {
                Log::warning('qpdf no pudo normalizar un archivo PDF.', [
                    'source' => $sourcePath,
                    'exit_code' => $result->exitCode(),
                    'error' => trim($result->errorOutput()),
                ]);

                throw new PdfNormalizationException(
                    'qpdf no pudo convertir el archivo. Verifique que el ejecutable esté instalado y disponible.'
                );
            }

            if ($completedWithWarnings) {
                Log::warning('qpdf normalizó un PDF con advertencias recuperables.', [
                    'source' => $sourcePath,
                    'warning' => trim($result->errorOutput()),
                ]);
            }

            if (! $hasOutput) {
                throw new PdfNormalizationException('qpdf terminó sin generar un PDF normalizado.');
            }

            return $outputPath;
        } catch (PdfNormalizationException $e) {
            @unlink($outputPath);

            throw $e;
        } catch (Throwable $e) {
            @unlink($outputPath);

            Log::warning('No fue posible ejecutar qpdf.', [
                'source' => $sourcePath,
                'error' => $e->getMessage(),
            ]);

            throw new PdfNormalizationException(
                'No fue posible ejecutar qpdf. Verifique la configuración QPDF_BINARY.',
                previous: $e
            );
        }
    }
}
