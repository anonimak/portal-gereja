<?php

declare(strict_types=1);

namespace App\DTO;

final class ImportSummaryDTO
{
    /**
     * @param array<int, array{row: int, column: string, value: mixed, message: string}> $errors
     */
    public function __construct(
        public readonly int $totalRowsRead,
        public readonly int $totalCreated,
        public readonly int $totalUpdated,
        public readonly int $totalSkipped,
        public readonly array $errors = [],
    ) {}

    public function isSuccess(): bool
    {
        return empty($this->errors);
    }

    public function hasFailures(): bool
    {
        return ! empty($this->errors);
    }
}
