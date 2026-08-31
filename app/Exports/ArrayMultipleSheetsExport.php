<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Export Excel multi-sheet dari array (Fase 3A).
 * Setiap sheet: title, headers, rows, opsional options styling.
 *
 * @phpstan-import-type SheetOptions from ArraySheetExport
 */
final class ArrayMultipleSheetsExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * @param  array<int, array{title: string, headers: array<int, string>, rows: array<int, array<int, mixed>>, options?: SheetOptions}>  $sheets
     */
    public function __construct(private readonly array $sheets) {}

    public function sheets(): array
    {
        return array_map(
            fn (array $sheet) => new ArraySheetExport(
                $sheet['title'],
                $sheet['headers'],
                $sheet['rows'],
                $sheet['options'] ?? [],
            ),
            $this->sheets
        );
    }
}
