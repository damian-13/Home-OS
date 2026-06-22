<?php

namespace App\Health\Infrastructure\Document;

use App\Health\Domain\Model\HealthDocument;

final readonly class DocumentTextExtractor
{
    public function __construct(
        private string $healthDocumentsDir,
    ) {
    }

    /**
     * @return array{status: string, text: string, message: string|null}
     */
    public function extract(HealthDocument $document): array
    {
        $path = sprintf('%s/%s', rtrim($this->healthDocumentsDir, '/'), $document->storedName());

        if (!is_file($path)) {
            return [
                'status' => 'missing_file',
                'text' => '',
                'message' => 'Stored file was not found.',
            ];
        }

        if ($document->mimeType() === 'application/pdf') {
            return $this->extractPdf($path);
        }

        if (str_starts_with($document->mimeType(), 'image/')) {
            return $this->extractImage($path);
        }

        return [
            'status' => 'unsupported',
            'text' => '',
            'message' => 'This file type is not supported for extraction.',
        ];
    }

    /**
     * @return array{status: string, text: string, message: string|null}
     */
    private function extractPdf(string $path): array
    {
        if (!$this->commandExists('pdftotext')) {
            return [
                'status' => 'tool_missing',
                'text' => '',
                'message' => 'PDF text extraction requires pdftotext. Rebuild the backend Docker image.',
            ];
        }

        return $this->runExtractionCommand(sprintf('pdftotext -layout %s -', escapeshellarg($path)));
    }

    /**
     * @return array{status: string, text: string, message: string|null}
     */
    private function extractImage(string $path): array
    {
        if (!$this->commandExists('tesseract')) {
            return [
                'status' => 'tool_missing',
                'text' => '',
                'message' => 'Image OCR requires Tesseract. Rebuild the backend Docker image.',
            ];
        }

        return $this->runExtractionCommand(sprintf('tesseract %s stdout -l pol+eng', escapeshellarg($path)));
    }

    private function commandExists(string $command): bool
    {
        $output = [];
        $exitCode = 1;
        exec(sprintf('command -v %s >/dev/null 2>&1', escapeshellarg($command)), $output, $exitCode);

        return $exitCode === 0;
    }

    /**
     * @return array{status: string, text: string, message: string|null}
     */
    private function runExtractionCommand(string $command): array
    {
        $process = proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            return [
                'status' => 'failed',
                'text' => '',
                'message' => 'Could not start text extraction.',
            ];
        }

        $text = $this->normalizeText(stream_get_contents($pipes[1]) ?: '');
        $error = trim(stream_get_contents($pipes[2]) ?: '');
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            return [
                'status' => 'failed',
                'text' => '',
                'message' => $error !== '' ? $error : 'Text extraction failed.',
            ];
        }

        if ($text === '') {
            return [
                'status' => 'empty',
                'text' => '',
                'message' => 'No text was found. This may be a scanned PDF that needs OCR.',
            ];
        }

        return [
            'status' => 'extracted',
            'text' => $text,
            'message' => null,
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r", "\f"], ["\n", "\n", "\n"], $text);
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
