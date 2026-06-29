<?php

namespace App\Shared\Infrastructure\File;

use App\Shared\Domain\Uuid;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class SafeUploadedFileStorage
{
    private const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    private const ALLOWED_EXTENSIONS_BY_MIME_TYPE = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
        'image/webp' => ['webp'],
    ];

    /**
     * @return array{originalName: string, storedName: string, mimeType: string, size: int}
     */
    public static function store(UploadedFile $file, string $householdId, string $baseDir): array
    {
        if (!$file->isValid()) {
            throw new InvalidArgumentException('Valid document file is required.');
        }

        $size = $file->getSize();
        if ($size === null || $size <= 0) {
            throw new InvalidArgumentException('Document file is empty.');
        }

        if ($size > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException('Document can be up to 10 MB.');
        }

        $mimeType = self::detectMimeType($file);
        if (!array_key_exists($mimeType, self::ALLOWED_EXTENSIONS_BY_MIME_TYPE)) {
            throw new InvalidArgumentException('Only PDF, JPG, PNG, and WebP files are supported.');
        }

        $originalName = self::safeOriginalName($file->getClientOriginalName());
        $clientExtension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        $allowedExtensions = self::ALLOWED_EXTENSIONS_BY_MIME_TYPE[$mimeType];

        if ($clientExtension === '' || !in_array($clientExtension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Document file extension does not match the detected file type.');
        }

        $extension = $allowedExtensions[0];
        $storedName = sprintf('%s/%s.%s', $householdId, Uuid::new(), $extension);
        $targetDirectory = sprintf('%s/%s', rtrim($baseDir, '/'), $householdId);

        if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0775, true) && !is_dir($targetDirectory)) {
            throw new InvalidArgumentException('Could not create document directory.');
        }

        $file->move($targetDirectory, basename($storedName));

        return [
            'originalName' => $originalName,
            'storedName' => $storedName,
            'mimeType' => $mimeType,
            'size' => (int) $size,
        ];
    }

    public static function resolveStoredPath(string $baseDir, string $storedName): ?string
    {
        if (str_contains($storedName, "\0") || str_starts_with($storedName, '/') || str_contains($storedName, '..')) {
            return null;
        }

        $basePath = realpath($baseDir);
        $filePath = realpath(sprintf('%s/%s', rtrim($baseDir, '/'), $storedName));

        if ($basePath === false || $filePath === false || !str_starts_with($filePath, $basePath.DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($filePath) ? $filePath : null;
    }

    private static function detectMimeType(UploadedFile $file): string
    {
        $mimeType = $file->getClientMimeType();

        if (function_exists('finfo_open')) {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMimeType = $fileInfo ? finfo_file($fileInfo, $file->getPathname()) : false;

            if ($fileInfo) {
                finfo_close($fileInfo);
            }

            if (is_string($detectedMimeType) && $detectedMimeType !== '') {
                return $detectedMimeType;
            }
        }

        return $mimeType;
    }

    private static function safeOriginalName(string $originalName): string
    {
        $basename = basename(str_replace('\\', '/', $originalName));
        $basename = preg_replace('/[^A-Za-z0-9._ -]/', '_', $basename) ?? '';

        return trim($basename) !== '' ? mb_substr(trim($basename), 0, 180) : 'document';
    }
}
