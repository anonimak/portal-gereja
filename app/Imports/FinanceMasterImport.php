<?php

declare(strict_types=1);

namespace App\Imports;

use App\DTO\ImportSummaryDTO;
use App\Models\FinancialCategory;
use App\Models\Fund;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinanceMasterImport implements WithMultipleSheets, SkipsUnknownSheets
{
    public int $totalRowsRead = 0;

    public int $totalCreated = 0;

    public int $totalUpdated = 0;

    public int $totalSkipped = 0;

    /**
     * @var array<int, array{row: int, column: string, value: mixed, message: string}>
     */
    public array $errors = [];

    public function __construct(
        public readonly int $churchId,
    ) {}

    public function onUnknownSheet(string|int $sheetName): void
    {
        // Abaikan sheet petunjuk / sheet tak dikenal
    }

    public function sheets(): array
    {
        return [
            0 => new class($this) implements ToCollection, WithHeadingRow, SkipsEmptyRows {
                public function __construct(protected FinanceMasterImport $parent) {}

                public function collection(Collection $rows): void
                {
                    foreach ($rows as $index => $row) {
                        $rowNumber = $index + 2;
                        $rawName = $row['nama_pos_dana'] ?? null;
                        $name = $this->parent->sanitizeString($rawName);

                        if (empty($name)) {
                            continue;
                        }

                        $this->parent->totalRowsRead++;

                        $existing = Fund::where('church_id', $this->parent->churchId)
                            ->where('name', $name)
                            ->first();

                        if ($existing) {
                            $this->parent->totalUpdated++;
                        } else {
                            Fund::create([
                                'church_id' => $this->parent->churchId,
                                'name' => $name,
                            ]);
                            $this->parent->totalCreated++;
                        }
                    }
                }
            },
            1 => new class($this) implements ToCollection, WithHeadingRow, SkipsEmptyRows {
                public function __construct(protected FinanceMasterImport $parent) {}

                public function collection(Collection $rows): void
                {
                    foreach ($rows as $index => $row) {
                        $rowNumber = $index + 2;
                        $rawName = $row['nama_kategori'] ?? null;
                        $rawType = $row['tipe'] ?? null;

                        $name = $this->parent->sanitizeString($rawName);
                        $type = $this->parent->sanitizeString($rawType);

                        if (empty($name) && empty($type)) {
                            continue;
                        }

                        $this->parent->totalRowsRead++;

                        if (empty($name)) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'nama_kategori',
                                'value' => '',
                                'message' => 'Nama kategori transaksi keuangan wajib diisi.',
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        $normalizedType = $this->parent->normalizeType($type);
                        if ($normalizedType === null) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'tipe',
                                'value' => (string) $rawType,
                                'message' => "Tipe '{$rawType}' tidak valid. Gunakan 'Pemasukan' (Debit) atau 'Pengeluaran' (Kredit).",
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        $existing = FinancialCategory::where('church_id', $this->parent->churchId)
                            ->where('name', $name)
                            ->first();

                        if ($existing) {
                            $existing->update(['type' => $normalizedType]);
                            $this->parent->totalUpdated++;
                        } else {
                            FinancialCategory::create([
                                'church_id' => $this->parent->churchId,
                                'name' => $name,
                                'type' => $normalizedType,
                            ]);
                            $this->parent->totalCreated++;
                        }
                    }
                }
            },
        ];
    }

    public function normalizeType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $t = strtolower(trim($type));

        return match ($t) {
            'pemasukan', 'masuk', 'debit', 'in' => 'debit',
            'pengeluaran', 'keluar', 'credit', 'kredit', 'out' => 'credit',
            default => null,
        };
    }

    public function sanitizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        if (in_array($str[0], ['=', '+', '-', '@'], true)) {
            $str = ltrim($str, "=+-@ \t\r\n");
        }

        return $str !== '' ? $str : null;
    }

    public function getSummary(): ImportSummaryDTO
    {
        return new ImportSummaryDTO(
            totalRowsRead: $this->totalRowsRead,
            totalCreated: $this->totalCreated,
            totalUpdated: $this->totalUpdated,
            totalSkipped: $this->totalSkipped,
            errors: $this->errors,
        );
    }
}
