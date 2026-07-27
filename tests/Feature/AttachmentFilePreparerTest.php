<?php

namespace Tests\Feature;

use App\Services\AttachmentFilePreparer;
use App\Support\PreparedAttachment;
use Tests\TestCase;

class AttachmentFilePreparerTest extends TestCase
{
    public function test_it_extracts_a_pdf_wrapped_in_an_http_response_from_a_bin_file(): void
    {
        $sourcePath = storage_path('app/http-wrapped-attachment.bin');
        $httpResponse = "HTTP/1.0 200 OK\r\n"
            ."Content-Disposition: inline; filename=\"Factura.pdf\"\r\n"
            ."Content-Type: application/pdf\r\n\r\n"
            ."%PDF-1.7\nsynthetic test";

        file_put_contents($sourcePath, $httpResponse);
        $prepared = null;

        try {
            $prepared = app(AttachmentFilePreparer::class)->prepare(
                $sourcePath,
                'application/octet-stream'
            );

            $this->assertSame(PreparedAttachment::PDF, $prepared->type);
            $this->assertTrue($prepared->temporary);
            $this->assertNotSame($sourcePath, $prepared->path);
            $this->assertStringStartsWith('%PDF-1.7', file_get_contents($prepared->path));

            $temporaryPath = $prepared->path;
            $prepared->cleanup();
            $this->assertFileDoesNotExist($temporaryPath);
        } finally {
            @unlink($sourcePath);
            $prepared?->cleanup();
        }
    }

    public function test_it_recognizes_a_jpeg_by_signature_even_with_a_bin_extension(): void
    {
        $sourcePath = storage_path('app/renamed-image.bin');
        file_put_contents($sourcePath, "\xFF\xD8\xFF\xE0synthetic jpeg");

        try {
            $prepared = app(AttachmentFilePreparer::class)->prepare(
                $sourcePath,
                'application/octet-stream'
            );

            $this->assertSame(PreparedAttachment::IMAGE, $prepared->type);
            $this->assertSame('JPG', $prepared->imageType);
            $this->assertSame($sourcePath, $prepared->path);
            $this->assertFalse($prepared->temporary);
        } finally {
            @unlink($sourcePath);
        }
    }
}
