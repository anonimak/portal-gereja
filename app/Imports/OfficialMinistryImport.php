<?php

declare(strict_types=1);

namespace App\Imports;

use App\DTO\ImportSummaryDTO;
use App\Models\Member;
use App\Models\MinistryRole;
use App\Models\Official;
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

class OfficialMinistryImport implements WithMultipleSheets, SkipsUnknownSheets
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
                public function __construct(protected OfficialMinistryImport $parent) {}

                public function collection(Collection $rows): void
                {
                    foreach ($rows as $index => $row) {
                        $rawName = $row['nama_jabatan'] ?? null;
                        $name = $this->parent->sanitizeString($rawName);

                        if (empty($name)) {
                            continue;
                        }

                        $this->parent->totalRowsRead++;

                        $existing = MinistryRole::where('church_id', $this->parent->churchId)
                            ->where('name', $name)
                            ->first();

                        if ($existing) {
                            $this->parent->totalUpdated++;
                        } else {
                            MinistryRole::create([
                                'church_id' => $this->parent->churchId,
                                'name' => $name,
                            ]);
                            $this->parent->totalCreated++;
                        }
                    }
                }
            },
            1 => new class($this) implements ToCollection, WithHeadingRow, SkipsEmptyRows {
                public function __construct(protected OfficialMinistryImport $parent) {}

                public function collection(Collection $rows): void
                {
                    foreach ($rows as $index => $row) {
                        $rowNumber = $index + 2;

                        $rawType = $row['tipe_pejabat'] ?? null;
                        $rawNik = $row['nik_anggota'] ?? null;
                        $rawName = $row['nama_pejabat'] ?? null;
                        $rawOrigin = $row['gereja_asal'] ?? null;
                        $rawStartDate = $row['tanggal_mulai_jabatan'] ?? null;
                        $rawEndDate = $row['tanggal_selesai_jabatan'] ?? null;

                        $type = $this->parent->normalizeOfficialType($this->parent->sanitizeString($rawType));
                        $nik = $this->parent->sanitizeString($rawNik);
                        $name = $this->parent->sanitizeString($rawName);
                        $originChurch = $this->parent->sanitizeString($rawOrigin);
                        $startDate = $this->parent->parseDate($rawStartDate);
                        $endDate = $this->parent->parseDate($rawEndDate);

                        if (empty($type) && empty($name) && empty($nik)) {
                            continue;
                        }

                        $this->parent->totalRowsRead++;

                        if ($type === null) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'tipe_pejabat',
                                'value' => (string) $rawType,
                                'message' => "Tipe pejabat '{$rawType}' tidak sah. Gunakan majelis_lokal, pendeta_internal, atau pelayan_tamu.",
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        if ($startDate === null) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'tanggal_mulai_jabatan',
                                'value' => (string) $rawStartDate,
                                'message' => 'Tanggal mulai jabatan wajib diisi dengan format YYYY-MM-DD.',
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        if (! empty($rawEndDate) && $endDate === null) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'tanggal_selesai_jabatan',
                                'value' => (string) $rawEndDate,
                                'message' => "Format tanggal selesai jabatan '{$rawEndDate}' tidak valid.",
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        // Resolusi Anggota
                        $member = null;
                        if (! empty($nik)) {
                            $cleanNik = preg_replace('/\D/', '', $nik);
                            $member = Member::where('church_id', $this->parent->churchId)
                                ->where('id_card_number', $cleanNik)
                                ->first();
                        }

                        if (! $member && ! empty($name) && in_array($type, ['majelis_lokal', 'pendeta_internal'], true)) {
                            $member = Member::where('church_id', $this->parent->churchId)
                                ->where('full_name', $name)
                                ->first();
                        }

                        if ($type === 'majelis_lokal' && ! $member) {
                            $identifier = $nik ?: $name;
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'nik_anggota',
                                'value' => (string) $identifier,
                                'message' => "Anggota jemaat '{$identifier}' untuk majelis lokal tidak ditemukan pada gereja ini.",
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        if ($type === 'pelayan_tamu' && empty($name)) {
                            $this->parent->errors[] = [
                                'row' => $rowNumber,
                                'column' => 'nama_pejabat',
                                'value' => '',
                                'message' => 'Nama pejabat wajib diisi untuk pelayan tamu.',
                            ];
                            $this->parent->totalSkipped++;
                            continue;
                        }

                        // Buat atau update Official
                        $existing = null;
                        if ($member) {
                            $existing = Official::where('church_id', $this->parent->churchId)
                                ->where('member_id', $member->id)
                                ->where('type', $type)
                                ->where('start_date', $startDate)
                                ->first();
                        } elseif (! empty($name)) {
                            $existing = Official::where('church_id', $this->parent->churchId)
                                ->where('external_name', $name)
                                ->where('type', $type)
                                ->where('start_date', $startDate)
                                ->first();
                        }

                        $dataToSave = [
                            'church_id' => $this->parent->churchId,
                            'type' => $type,
                            'member_id' => $member?->id,
                            'external_name' => $member ? null : $name,
                            'origin_church' => $originChurch,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                        ];

                        if ($existing) {
                            $existing->update($dataToSave);
                            $this->parent->totalUpdated++;
                        } else {
                            Official::create($dataToSave);
                            $this->parent->totalCreated++;
                        }
                    }
                }
            },
        ];
    }

    public function normalizeOfficialType(?string $type): ?string
    {
        if ($type === null) {
            return null;
        }

        $t = strtolower(trim($type));

        return match ($t) {
            'majelis_lokal', 'majelis', 'penatua', 'diaken' => 'majelis_lokal',
            'pendeta_internal', 'pendeta' => 'pendeta_internal',
            'pelayan_tamu', 'tamu' => 'pelayan_tamu',
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
