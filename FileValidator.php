<?php
declare(strict_types=1);

class FileValidator
{
    public const UNIT_B = 'B';
    public const UNIT_KB = 'KB';
    public const UNIT_MB = 'MB';
    public const UNIT_GB = 'GB';

    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    private array $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    private array $units;
    private int $maxSizeInBytes = 0;
    private int $maxTotalSizeInBytes = 0;

    public function getMultipleFiles(array $file): array
    {
        return array_map(
            fn(...$values) => array_combine(array_keys($file), $values),
            ...array_column(array_values($file), null)
        );
    }

    public function getUnits(): array
    {
        return $this->units;
    }

    public function setAllowedExtensions(array $extensions): void
    {
        $this->allowedExtensions = $extensions;
    }

    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    public function setAllowedTypes(array $types): void
    {
        $this->allowedTypes = $types;
    }

    public function setMaxSize(int $size, string $unit): void
    {
        $bytes = $this->convertToBytes($size, $unit);
        $this->units['maxSize'] = compact('size', 'unit', 'bytes');
        $this->maxSizeInBytes = $bytes;
    }

    public function setTotalMaxSize(int $size, string $unit): void
    {
        $bytes = $this->convertToBytes($size, $unit);
        $this->units['totalMaxSize'] = compact('size', 'unit', 'bytes');
        $this->maxTotalSizeInBytes = $bytes;
    }

    public function validateFile(array $file): void
    {
        if (!$this->isMultiple($file)) {
            $this->validateSingleFile($file);
            return;
        }

        $files = $this->getMultipleFiles($file);
        $totalSize = array_sum(array_column($files, 'size'));
        $this->validateTotalFileSize($totalSize);
        array_walk($files, [$this, 'validateSingleFile']);
    }

    public function isMultiple(array $file): bool
    {
        return is_array($file['name']);
    }

    private function validateSingleFile(array $file): void
    {
        $this->validateFileData($file);
        $this->validateFileExtension($file);
        $this->validateFileType($file);
        $this->validateFileSize($file);
    }

    private function validateFileData(array $file): void
    {
        $requiredKeys = ['name', 'type', 'tmp_name', 'error', 'size'];
        $missingKeys = array_diff($requiredKeys, array_keys($file));
        if (!empty($missingKeys)) {
            throw new Exception('Invalid file data provided');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload failed');
        }
    }

    private function validateFileExtension(array $file): void
    {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception('Invalid file extension');
        }
    }

    private function validateFileType(array $file): void
    {
        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('Invalid file type');
        }
    }

    private function validateFileSize(array $file): void
    {
        if ($this->maxSizeInBytes > 0 && $file['size'] > $this->maxSizeInBytes) {
            throw new Exception('File is too large');
        }
    }

    private function validateTotalFileSize(int $totalSize): void
    {
        if ($this->maxTotalSizeInBytes > 0 && $totalSize > $this->maxTotalSizeInBytes) {
            throw new Exception('Total files are too large');
        }
    }

    private function convertToBytes(int $size, string $unit): int
    {
        $units = [
            self::UNIT_B => 1,
            self::UNIT_KB => 1024,
            self::UNIT_MB => 1024 * 1024,
            self::UNIT_GB => 1024 * 1024 * 1024,
        ];

        $unit = strtoupper($unit);
        if (!isset($units[$unit])) {
            throw new \InvalidArgumentException('Invalid unit: ' . $unit);
        }

        return $size * $units[$unit];
    }
}