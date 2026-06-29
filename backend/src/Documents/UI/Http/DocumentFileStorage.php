<?php

namespace App\Documents\UI\Http;

use App\Shared\Domain\Uuid;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class DocumentFileStorage
{
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const EXTENSIONS_BY_MIME_TYPE = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @return array{originalName: string, storedName: string, mimeType: string, size: int}
     */
    public static function store(UploadedFile $file, string $householdId, string $documentsDir): array
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Valid document file is required.');
        }

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();

        if (function_exists('finfo_open')) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMimeType = $fileInfo ? finfo_file($fileInfo, $file->getPathname()) : false;

            if ($fileInfo) {
                finfo_close($fileInfo);
            }

            if (is_string($detectedMimeType) && $detectedMimeType !== '') {
                $mimeType = $detectedMimeType;
            }
        }

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Only PDF, JPG, PNG, and WebP files are supported.');
        }

        if ($file->getSize() !== null && $file->getSize() > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('Document can be up to 10 MB.');
        }

        $size = (int) $file->getSize();
        $extension = self::EXTENSIONS_BY_MIME_TYPE[$mimeType];
        $storedName = sprintf('%s/%s.%s', $householdId, Uuid::new(), strtolower($extension));
        $targetDirectory = sprintf('%s/%s', rtrim($documentsDir, '/'), $householdId);

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new InvalidArgumentException('Could not create document directory.');
        }

        $file->move($targetDirectory, basename($storedName));

        return [
            'originalName' => $originalName,
            'storedName' => $storedName,
            'mimeType' => $mimeType,
            'size' => $size,
        ];
    }
}
