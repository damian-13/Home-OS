<?php

namespace App\Documents\UI\Http;

use App\Shared\Infrastructure\File\SafeUploadedFileStorage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class DocumentFileStorage
{
    /**
     * @return array{originalName: string, storedName: string, mimeType: string, size: int}
     */
    public static function store(UploadedFile $file, string $householdId, string $documentsDir): array
    {
        return SafeUploadedFileStorage::store($file, $householdId, $documentsDir);
    }
}
