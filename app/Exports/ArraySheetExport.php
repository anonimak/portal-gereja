<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Sheet Excel dari array (Fase 3A).
 */
final class ArraySheetExport implements FromArray, WithTitle
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headers,
        private readonly array $rows,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        return array_merge([$this->headers], $this->rows);
    }
}
