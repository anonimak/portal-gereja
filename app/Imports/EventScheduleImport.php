<?php

declare(strict_types=1);

namespace App\Imports;

use App\DTO\ImportSummaryDTO;
use App\Models\EventCategory;
use App\Models\RecurringSchedule;
use Carbon\Carbon;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class EventScheduleImport implements WithMultipleSheets, SkipsUnknownSheets
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
                public function __construct(protected EventScheduleImport $parent) {}

                public function collection(Collection $rows): void
                {
                    foreach ($rows as $index => $row) {
                        $rawName = $row['nama_kategori'] ?? null;
                        $name = $this->parent->sanitizeString($rawName);

                        if (empty($name)) {
                            continue;
                        }

                        $this->parent->totalRowsRead++;

                        $existing = EventCategory::where('church_id', $this->parent->churchId)
                            ->where('name', $name)
                            ->first();

                        if ($existing) {
                            $this->parent->totalUpdated++;
                        } else {
                            EventCategory::create([
                                'church_id' => $this->parent->churchId,
                                'name' => $name,
                            ]);
                            $this->parent->totalCreated++;
                        }
                    }
                }
            },
            1 => new class($this) implements ToCollection, WithHeadingRow, SkipsEmptyRows {
                public function __construct(protected EventScheduleImport $parent) {}

                public function collection(Collection $rows): void
                {
                    foreach ($rows as $index => $row) {
                        $rowNumber = $index + 2;

                        $rawCategory = $row['kategori'] ?? null;
                        $rawTitle = $row['judul_jadwal'] ?? null;
                        $rawLocation = $row['lokasi'] ?? null;
                        $rawFrequency = $row['frekuensi'] ?? null;
                        $rawDay = $row['hari_pelaksanaan'] ?? null;
                        $rawStartTime = $row['jam_mulai'] ?? null;
                        $rawEndTime = $row['jam_selesai'] ?? null;
                        $rawStartDate = $row['tanggal_mulai_berlaku'] ?? null;
                        $rawDescription = $row['keterangan'] ?? null;

                        $categoryName = $this->parent->sanitizeString($rawCategory);
                        $title = $this->parent->sanitizeString($rawTitle);
                        $location = $this->parent->sanitizeString($rawLocation);
                        $frequency = $this->parent->normalizeFrequency($this->parent->sanitizeString($rawFrequency));
                        $daysOfWeek = $this->parent->mapDayToNumber($this->parent->sanitizeString($rawDay));
                        $startTime = $this->parent->parseTime($rawStartTime);
                        $endTime = $this->parent->parseTime($rawEndTime);
                        $startDate = $this->parent->parseDate($rawStartDate);
                        $description = $this->parent->sanitizeString($rawDescription);

                        if (empty($title) && empty($categoryName)) {
                            continue;
                        }

                        $this->parent->totalRowsRead++;

                        if (empty($title)) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'judul_jadwal',
                                'value' => '',
                                'message' => 'Judul jadwal ibadah/acara wajib diisi.',
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        if ($startDate === null) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'tanggal_mulai_berlaku',
                                'value' => (string) $rawStartDate,
                                'message' => 'Tanggal mulai berlaku jadwal wajib diisi dengan format YYYY-MM-DD.',
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        // Resolusi Kategori: Buat otomatis jika belum ada
                        $category = null;
                        if (! empty($categoryName)) {
                            $category = EventCategory::firstOrCreate(
                                ['church_id' => $this->parent->churchId, 'name' => $categoryName]
                            );
                        }

                        $existing = RecurringSchedule::where('church_id', $this->parent->churchId)
                            ->where('title', $title)
                            ->when($category, fn ($q) => $q->where('category_id', $category->id))
                            ->first();

                        $scheduleData = [
                            'church_id' => $this->parent->churchId,
                            'category_id' => $category?->id,
                            'title' => $title,
                            'description' => $description,
                            'location' => $location ?: 'Gedung Gereja Utama',
                            'frequency' => $frequency,
                            'interval' => 1,
                            'days_of_week' => $daysOfWeek,
                            'start_date' => $startDate,
                            'start_time' => $startTime ?: '08:30:00',
                            'end_time' => $endTime ?: '10:30:00',
                            'end_type' => 'never',
                            'is_active' => true,
                        ];

                        if ($existing) {
                            $existing->update($scheduleData);
                            $this->parent->totalUpdated++;
                        } else {
                            RecurringSchedule::create($scheduleData);
                            $this->parent->totalCreated++;
                        }
                    }
                }
            },
        ];
    }

    public function normalizeFrequency(?string $frequency): string
    {
        if ($frequency === null) {
            return 'weekly';
        }

        $f = strtolower(trim($frequency));

        return match ($f) {
            'monthly', 'bulanan' => 'monthly',
            'daily', 'harian' => 'daily',
            default => 'weekly',
        };
    }

    /**
     * @return array<int, int>
     */
    public function mapDayToNumber(?string $day): array
    {
        if ($day === null) {
            return [0]; // Default Minggu
        }

        $d = strtolower(trim($day));

        return match ($d) {
            'senin', 'monday', '1' => [1],
            'selasa', 'tuesday', '2' => [2],
            'rabu', 'wednesday', '3' => [3],
            'kamis', 'thursday', '4' => [4],
            'jumat', 'friday', '5' => [5],
            'sabtu', 'saturday', '6' => [6],
            'minggu', 'ahad', 'sunday', '0' => [0],
            default => [0],
        };
    }

    public function parseTime(mixed $time): ?string
    {
        if (empty($time)) {
            return null;
        }

        $str = trim((string) $time);

        // Jika bentuk pecahan hari Excel (e.g. 0.35416666666667)
        if (is_numeric($str) && (float) $str < 1 && (float) $str > 0) {
            $totalSeconds = (int) round(((float) $str) * 86400);
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);

            return sprintf('%02d:%02d:00', $hours, $minutes);
        }

        if (preg_match('/^(\d{1,2}):(\d{2})(:(\d{2}))?$/', $str, $matches)) {
            $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minutes = $matches[2];
            $seconds = $matches[4] ?? '00';

            return "{$hours}:{$minutes}:{$seconds}";
        }

        return null;
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

    public function parseDate(mixed $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            $num = (float) $value;
            if ($num > 1000) {
                try {
                    return ExcelDate::excelToDateTimeObject($num)->format('Y-m-d');
                } catch (\Throwable) {
                    // Fallback
                }
            }
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'] as $fmt) {
            $d = DateTimeImmutable::createFromFormat($fmt, $str);
            if ($d && $d->format($fmt) === $str) {
                return $d->format('Y-m-d');
            }
        }

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
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
