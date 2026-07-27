<?php

namespace App\Services;

use App\Support\PreparedAttachment;
use Illuminate\Support\Str;
use RuntimeException;

final class AttachmentFilePreparer
{
    private const INSPECTION_BYTES = 65536;

    public function prepare(string $path, ?string $declaredMime = null): PreparedAttachment
    {
        $head = $this->readHead($path);
        $payloadOffset = $this->httpPayloadOffset($head);

        if ($payloadOffset === false) {
            return new PreparedAttachment(PreparedAttachment::UNSUPPORTED, $path);
        }

        $payloadHead = substr($head, $payloadOffset);
        $pdfPosition = strpos(substr($payloadHead, 0, 1024), '%PDF-');

        if ($pdfPosition !== false) {
            return $this->resultForPayload(
                $path,
                $payloadOffset + $pdfPosition,
                PreparedAttachment::PDF,
                'pdf'
            );
        }

        $imageType = $this->imageTypeFromSignature($payloadHead);
        if ($imageType !== null) {
            return $this->resultForPayload(
                $path,
                $payloadOffset,
                PreparedAttachment::IMAGE,
                strtolower($imageType),
                $imageType
            );
        }

        // Preserve compatibility for normal files whose MIME was already
        // identified reliably, but never trust HTTP headers without a signature.
        if ($payloadOffset === 0 && str_contains(strtolower((string) $declaredMime), 'pdf')) {
            return new PreparedAttachment(PreparedAttachment::PDF, $path);
        }

        if ($payloadOffset === 0) {
            $detectedType = @exif_imagetype($path);
            $imageType = $this->fpdfImageType($detectedType);

            if ($imageType !== null) {
                return new PreparedAttachment(PreparedAttachment::IMAGE, $path, $imageType);
            }
        }

        return new PreparedAttachment(PreparedAttachment::UNSUPPORTED, $path);
    }

    private function readHead(string $path): string
    {
        $handle = @fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("No se pudo abrir el archivo adjunto: {$path}");
        }

        try {
            $head = fread($handle, self::INSPECTION_BYTES);
        } finally {
            fclose($handle);
        }

        if ($head === false) {
            throw new RuntimeException("No se pudo leer el archivo adjunto: {$path}");
        }

        return $head;
    }

    /**
     * Returns zero for a regular file, the body offset for an HTTP-wrapped file,
     * or false when an HTTP response has no complete header separator.
     */
    private function httpPayloadOffset(string $head): int|false
    {
        if (!preg_match('/\AHTTP\/\d(?:\.\d)?\s+\d{3}\b/i', $head)) {
            return 0;
        }

        $crlfPosition = strpos($head, "\r\n\r\n");
        if ($crlfPosition !== false) {
            return $crlfPosition + 4;
        }

        $lfPosition = strpos($head, "\n\n");

        return $lfPosition === false ? false : $lfPosition + 2;
    }

    private function imageTypeFromSignature(string $head): ?string
    {
        if (str_starts_with($head, "\xFF\xD8\xFF")) {
            return 'JPG';
        }

        if (str_starts_with($head, "\x89PNG\r\n\x1A\n")) {
            return 'PNG';
        }

        if (str_starts_with($head, 'GIF87a') || str_starts_with($head, 'GIF89a')) {
            return 'GIF';
        }

        return null;
    }

    private function fpdfImageType(int|false $imageType): ?string
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => 'JPG',
            IMAGETYPE_PNG => 'PNG',
            IMAGETYPE_GIF => 'GIF',
            default => null,
        };
    }

    private function resultForPayload(
        string $sourcePath,
        int $payloadOffset,
        string $type,
        string $extension,
        ?string $imageType = null,
    ): PreparedAttachment {
        if ($payloadOffset === 0) {
            return new PreparedAttachment($type, $sourcePath, $imageType);
        }

        $tempDirectory = storage_path('app/temp');
        if (!is_dir($tempDirectory) && !mkdir($tempDirectory, 0775, true) && !is_dir($tempDirectory)) {
            throw new RuntimeException('No se pudo crear el directorio temporal para adjuntos.');
        }

        $tempPath = $tempDirectory.DIRECTORY_SEPARATOR.'attachment_'.Str::uuid().'.'.$extension;
        $source = @fopen($sourcePath, 'rb');
        $target = @fopen($tempPath, 'wb');

        if ($source === false || $target === false) {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($target)) {
                fclose($target);
            }
            @unlink($tempPath);
            throw new RuntimeException('No se pudo preparar temporalmente el archivo adjunto.');
        }

        $copyError = null;

        try {
            if (fseek($source, $payloadOffset) !== 0 || stream_copy_to_stream($source, $target) === false) {
                throw new RuntimeException('No se pudo extraer el contenido del archivo adjunto.');
            }
        } catch (\Throwable $e) {
            $copyError = $e;
        } finally {
            fclose($source);
            fclose($target);
        }

        if ($copyError !== null) {
            @unlink($tempPath);
            throw $copyError;
        }

        return new PreparedAttachment($type, $tempPath, $imageType, true);
    }
}
