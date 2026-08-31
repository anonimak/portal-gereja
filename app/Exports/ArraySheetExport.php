<?php

declare(strict_types=1);

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Sheet Excel dari array (Fase 3A) + styling export (header, border, zebra,
 * auto-width, total row, currency format).
 *
 * @phpstan-type SheetOptions array{totalRows?: int, currencyColumns?: array<int, int>}
 */
final class ArraySheetExport implements FromArray, WithTitle, WithStyles, WithEvents, ShouldAutoSize, WithColumnWidths, WithStrictNullComparison
{
    private const HEADER_FILL = 'FF1F4E78';

    private const ZEBRA_FILL = 'FFF3F6FB';

    private const TOTAL_FILL = 'FFE2E8F0';

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     * @param  SheetOptions  $options
     */
    public function __construct(
        private readonly string $title,
        private readonly array $headers,
        private readonly array $rows,
        private readonly array $options = [],
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    public function array(): array
    {
        return $this->values();
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function values(): array
    {
        $rows = array_map(
            fn (array $row) => $this->normalizeRow($row),
            $this->rows
        );

        return array_merge([$this->headers], $rows);
    }

    /**
     * Kolom currency dikonversi dari string ribuan ('1.234') ke numerik agar
     * tampil rapi sebagai angka di Excel.
     *
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    private function normalizeRow(array $row): array
    {
        foreach ($this->options['currencyColumns'] ?? [] as $col) {
            $index = $col - 1;
            if (! array_key_exists($index, $row)) {
                continue;
            }

            $value = $row[$index];
            if (is_string($value) && preg_match('/^-?[\d.]+$/', trim($value))) {
                $row[$index] = (float) str_replace('.', '', trim($value));
            }
        }

        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];

        // Header: bold + fill biru gelap + font putih.
        $styles[1] = [
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => self::HEADER_FILL],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];

        // Zebra striping pada body.
        $rowCount = count($this->rows);
        for ($r = 2; $r <= $rowCount + 1; $r++) {
            if ($r % 2 === 0) {
                $styles[$r] = [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => self::ZEBRA_FILL],
                    ],
                ];
            }
        }

        // Total row (footer): bold + background berbeda.
        $totalRows = $this->options['totalRows'] ?? 0;
        if ($totalRows > 0) {
            $firstTotal = $rowCount + 2 - $totalRows;
            for ($r = $firstTotal; $r <= $rowCount + 1; $r++) {
                $styles[$r] = [
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => self::TOTAL_FILL],
                    ],
                ];
            }
        }

        return $styles;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastColumn = $sheet->getHighestDataColumn();
                $lastRow = $sheet->getHighestDataRow();

                // Border tipis seluruh range.
                $sheet->getStyle("A1:{$lastColumn}{$lastRow}")->getBorders()->applyFromArray([
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN],
                ]);

                // Rata kanan + format angka #,##0 untuk kolom currency.
                foreach ($this->options['currencyColumns'] ?? [] as $col) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $sheet->getStyle("{$colLetter}2:{$colLetter}{$lastRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("{$colLetter}2:{$colLetter}{$lastRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                }
            },
        ];
    }

    /**
     * @return array<string, int>
     */
    public function columnWidths(): array
    {
        $widths = [];
        $count = count($this->headers);
        for ($i = 1; $i <= $count; $i++) {
            $widths[Coordinate::stringFromColumnIndex($i)] = max(12, min(40, $this->maxLength($i)));
        }

        return $widths;
    }

    private function maxLength(int $column): int
    {
        $max = mb_strlen((string) ($this->headers[$column - 1] ?? ''));

        foreach ($this->rows as $row) {
            $value = $row[$column - 1] ?? '';
            $max = max($max, mb_strlen((string) $value));
        }

        return $max + 2;
    }
}
