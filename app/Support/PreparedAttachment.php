<?php

namespace App\Support;

final class PreparedAttachment
{
    public const PDF = 'pdf';
    public const IMAGE = 'image';
    public const UNSUPPORTED = 'unsupported';

    public function __construct(
        public readonly string $type,
        public readonly string $path,
        public readonly ?string $imageType = null,
        public readonly bool $temporary = false,
    ) {
    }

    public function cleanup(): void
    {
        if ($this->temporary && is_file($this->path)) {
            @unlink($this->path);
        }
    }
}
