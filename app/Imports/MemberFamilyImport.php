<?php

declare(strict_types=1);

namespace App\Imports;

use App\DTO\ImportSummaryDTO;
use App\Models\Family;
use App\Models\Member;
use App\Models\MemberSacrament;
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

class MemberFamilyImport implements ToCollection, WithHeadingRow, SkipsEmptyRows, WithMultipleSheets, SkipsUnknownSheets
{
    public int $totalRowsRead = 0;

    public int $totalCreated = 0;

    public int $totalUpdated = 0;

    public int $totalSkipped = 0;

    /**
     * @var array<int, array{row: int, column: string, value: mixed, message: string}>
     */
    public array $errors = [];

    /**
     * @var array<string, bool>
     */
    protected array $seenNiksInFile = [];

    public function __construct(
        public readonly int $churchId,
    ) {}

    public function onUnknownSheet(string|int $sheetName): void
    {
        // Abaikan sheet yang tidak terdaftar (misal: sheet petunjuk)
    }

    public function sheets(): array
    {
        return [
            0 => $this,
            'Data Jemaat' => $this,
        ];
    }

    /**
     * @param Collection<int, Collection<string, mixed>> $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Baris 1 adalah heading

            $noKk = $this->sanitizeString($row['no_kk'] ?? null);
            $namaLengkap = $this->sanitizeString($row['nama_lengkap'] ?? null);
            $nik = $this->sanitizeString($row['nik'] ?? null);

            // Lewati jika seluruh baris kosong
            if (empty($noKk) && empty($namaLengkap) && empty($nik)) {
                continue;
            }

            $this->totalRowsRead++;

            // 1. Validasi Kolom Wajib: no_kk
            if (empty($noKk)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'column' => 'no_kk',
                    'value' => '',
                    'message' => 'Nomor Kartu Keluarga (no_kk) wajib diisi.',
                ];
                $this->totalSkipped++;
                continue;
            }

            // 2. Validasi Kolom Wajib: nama_lengkap
            if (empty($namaLengkap)) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'column' => 'nama_lengkap',
                    'value' => '',
                    'message' => 'Nama lengkap jemaat wajib diisi.',
                ];
                $this->totalSkipped++;
                continue;
            }

            // 3. Normalisasi & Validasi jenis_kelamin
            $rawGender = $this->sanitizeString($row['jenis_kelamin'] ?? null);
            $gender = $this->normalizeGender($rawGender);
            if ($gender === null) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'column' => 'jenis_kelamin',
                    'value' => $rawGender,
                    'message' => "Jenis kelamin '{$rawGender}' tidak valid. Gunakan 'L' atau 'P'.",
                ];
                $this->totalSkipped++;
                continue;
            }

            // 4. Normalisasi hubungan_keluarga
            $rawRelation = $this->sanitizeString($row['hubungan_keluarga'] ?? null);
            $relation = $this->normalizeFamilyRelation($rawRelation);

            // 5. Normalisasi status_anggota
            $rawStatus = $this->sanitizeString($row['status_anggota'] ?? null);
            $status = $this->normalizeMemberStatus($rawStatus);

            // 6. Tanggal Lahir
            $rawBirthDate = $row['tanggal_lahir'] ?? null;
            $birthDate = $this->parseDate($rawBirthDate);
            if (! empty($rawBirthDate) && $birthDate === null) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'column' => 'tanggal_lahir',
                    'value' => (string) $rawBirthDate,
                    'message' => "Format tanggal lahir '{$rawBirthDate}' tidak valid. Gunakan format YYYY-MM-DD.",
                ];
                $this->totalSkipped++;
                continue;
            }

            // 7. Sakramen: Baptis, Sidi, Nikah
            $rawBaptisDate = $row['tanggal_baptis'] ?? null;
            $baptisDate = $this->parseDate($rawBaptisDate);
            if (! empty($rawBaptisDate) && $baptisDate === null) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'column' => 'tanggal_baptis',
                    'value' => (string) $rawBaptisDate,
                    'message' => "Format tanggal baptis '{$rawBaptisDate}' tidak valid. Gunakan YYYY-MM-DD.",
                ];
                $this->totalSkipped++;
                continue;
            }

            $rawSidiDate = $row['tanggal_sidi'] ?? null;
            $sidiDate = $this->parseDate($rawSidiDate);
            if (! empty($rawSidiDate) && $sidiDate === null) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'column' => 'tanggal_sidi',
                    'value' => (string) $rawSidiDate,
                    'message' => "Format tanggal sidi '{$rawSidiDate}' tidak valid. Gunakan YYYY-MM-DD.",
                ];
                $this->totalSkipped++;
                continue;
            }

            $rawNikahDate = $row['tanggal_nikah'] ?? null;
            $nikahDate = $this->parseDate($rawNikahDate);
            if (! empty($rawNikahDate) && $nikahDate === null) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'column' => 'tanggal_nikah',
                    'value' => (string) $rawNikahDate,
                    'message' => "Format tanggal nikah '{$rawNikahDate}' tidak valid. Gunakan YYYY-MM-DD.",
                ];
                $this->totalSkipped++;
                continue;
            }

            // 8. Deduplikasi & Sanitasi NIK
            if (! empty($nik)) {
                $nik = preg_replace('/\D/', '', $nik); // Ambil digit saja
                if ($nik !== '') {
                    // Cek duplikasi di file yang sama
                    if (isset($this->seenNiksInFile[$nik])) {
                        $this->errors[] = [
                            'row' => $rowNumber,
                            'column' => 'nik',
                            'value' => $nik,
                            'message' => "NIK '{$nik}' terdeteksi ganda di dalam file spreadsheet.",
                        ];
                        $this->totalSkipped++;
                        continue;
                    }
                    $this->seenNiksInFile[$nik] = true;

                    // Cek duplikasi di gereja lain (Integritas Multi-Tenant Vera)
                    $collisionOtherChurch = Member::withoutGlobalScopes()
                        ->where('id_card_number', $nik)
                        ->where('church_id', '!=', $this->churchId)
                        ->exists();

                    if ($collisionOtherChurch) {
                        $this->errors[] = [
                            'row' => $rowNumber,
                            'column' => 'nik',
                            'value' => $nik,
                            'message' => "NIK '{$nik}' sudah terdaftar pada jemaat gereja lain.",
                        ];
                        $this->totalSkipped++;
                        continue;
                    }
                } else {
                    $nik = null;
                }
            } else {
                $nik = null;
            }

            // 9. Kelompokkan ke Entitas Family
            $alamat = $this->sanitizeString($row['alamat'] ?? null) ?: '-';
            $namaKeluarga = $this->sanitizeString($row['nama_keluarga'] ?? null);

            $family = Family::where('church_id', $this->churchId)
                ->where('family_number', $noKk)
                ->first();

            if (! $family) {
                $family = Family::create([
                    'church_id' => $this->churchId,
                    'family_number' => $noKk,
                    'name' => $namaKeluarga ?: ('Kel. '.$namaLengkap),
                    'address' => $alamat,
                ]);
            } else {
                // Update alamat jika sebelumnya kosong/default
                if (($family->address === '-' || empty($family->address)) && $alamat !== '-') {
                    $family->update(['address' => $alamat]);
                }
                // Update nama keluarga jika sebelumnya pakai default
                if ($namaKeluarga && str_starts_with($family->name, 'Kel. ')) {
                    $family->update(['name' => $namaKeluarga]);
                }
            }

            // 10. Pencarian & Upsert Anggota (Member)
            $phone = $this->sanitizeString($row['nomor_telepon'] ?? null);
            $birthPlace = $this->sanitizeString($row['tempat_lahir'] ?? null);

            $member = null;
            if ($nik) {
                $member = Member::where('church_id', $this->churchId)
                    ->where('id_card_number', $nik)
                    ->first();
            }

            if (! $member) {
                $query = Member::where('church_id', $this->churchId)
                    ->where('family_id', $family->id)
                    ->where('full_name', $namaLengkap);

                if ($birthDate) {
                    $query->where('birth_date', $birthDate);
                }

                $member = $query->first();
            }

            $customFields = $member?->custom_fields ? (array) $member->custom_fields : [];
            if ($phone) {
                $customFields['phone'] = $phone;
            }

            $memberData = [
                'church_id' => $this->churchId,
                'family_id' => $family->id,
                'id_card_number' => $nik,
                'full_name' => $namaLengkap,
                'gender' => $gender,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'family_relation' => $relation,
                'status' => $status,
                'custom_fields' => $customFields,
            ];

            if ($member) {
                $member->update($memberData);
                $this->totalUpdated++;
            } else {
                $member = Member::create($memberData);
                $this->totalCreated++;
            }

            // 11. Pembuatan Otomatis Catatan Sakramen (member_sacraments)
            $this->processSacraments($member, $row, $birthDate, $baptisDate, $sidiDate, $nikahDate);
        }
    }

    /**
     * Proses otomatis sakramen Baptis, Sidi, dan Pernikahan.
     *
     * @param array<string, mixed>|Collection<string, mixed> $row
     */
    protected function processSacraments(
        Member $member,
        array|Collection $row,
        ?string $birthDate,
        ?string $baptisDate,
        ?string $sidiDate,
        ?string $nikahDate
    ): void {
        $statusBaptis = strtolower(trim((string) ($row['status_baptis'] ?? '')));
        $hasBaptis = in_array($statusBaptis, ['sudah', 'ya', 'yes', 'true', '1'], true) || ! empty($baptisDate);

        if ($hasBaptis) {
            $existingBaptis = MemberSacrament::where('member_id', $member->id)
                ->whereIn('type', ['baptis_anak', 'baptis_dewasa'])
                ->exists();

            if (! $existingBaptis) {
                $baptisType = 'baptis_dewasa';
                if ($birthDate && $baptisDate) {
                    $ageAtBaptis = Carbon::parse($birthDate)->diffInYears(Carbon::parse($baptisDate));
                    if ($ageAtBaptis < 15) {
                        $baptisType = 'baptis_anak';
                    }
                } elseif ($member->family_relation === 'anak') {
                    $baptisType = 'baptis_anak';
                }

                MemberSacrament::create([
                    'church_id' => $this->churchId,
                    'member_id' => $member->id,
                    'type' => $baptisType,
                    'sacrament_date' => $baptisDate ?: now()->toDateString(),
                    'certificate_number' => $this->sanitizeString($row['nomor_surat_baptis'] ?? null),
                ]);
            }
        }

        $statusSidi = strtolower(trim((string) ($row['status_sidi'] ?? '')));
        $hasSidi = in_array($statusSidi, ['sudah', 'ya', 'yes', 'true', '1'], true) || ! empty($sidiDate);

        if ($hasSidi) {
            $existingSidi = MemberSacrament::where('member_id', $member->id)
                ->where('type', 'sidi')
                ->exists();

            if (! $existingSidi) {
                MemberSacrament::create([
                    'church_id' => $this->churchId,
                    'member_id' => $member->id,
                    'type' => 'sidi',
                    'sacrament_date' => $sidiDate ?: now()->toDateString(),
                    'certificate_number' => $this->sanitizeString($row['nomor_surat_sidi'] ?? null),
                ]);
            }
        }

        $statusNikah = strtolower(trim((string) ($row['status_nikah'] ?? '')));
        $hasNikah = in_array($statusNikah, ['sudah', 'ya', 'yes', 'true', '1'], true) || ! empty($nikahDate);

        if ($hasNikah) {
            $existingNikah = MemberSacrament::where('member_id', $member->id)
                ->where('type', 'nikah')
                ->exists();

            if (! $existingNikah) {
                MemberSacrament::create([
                    'church_id' => $this->churchId,
                    'member_id' => $member->id,
                    'type' => 'nikah',
                    'sacrament_date' => $nikahDate ?: now()->toDateString(),
                    'certificate_number' => $this->sanitizeString($row['nomor_surat_nikah'] ?? null),
                ]);
            }
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

    protected function normalizeGender(?string $gender): ?string
    {
        if ($gender === null) {
            return null;
        }

        $g = strtolower(trim($gender));

        return match ($g) {
            'l', 'laki-laki', 'laki - laki', 'pria', 'm', 'male' => 'm',
            'p', 'perempuan', 'wanita', 'f', 'female' => 'f',
            default => null,
        };
    }

    protected function normalizeFamilyRelation(?string $relation): string
    {
        if ($relation === null) {
            return 'lainnya';
        }

        $r = strtolower(trim($relation));

        return match ($r) {
            'kepala_keluarga', 'kepala keluarga', 'suami', 'kk' => 'kepala_keluarga',
            'istri' => 'istri',
            'anak' => 'anak',
            default => 'lainnya',
        };
    }

    protected function normalizeMemberStatus(?string $status): string
    {
        if ($status === null) {
            return 'aktif';
        }

        $s = strtolower(trim($status));

        return match ($s) {
            'titipan' => 'titipan',
            'pindah' => 'pindah',
            'meninggal' => 'meninggal',
            default => 'aktif',
        };
    }

    protected function sanitizeString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);
        if ($str === '') {
            return null;
        }

        // Netralkan potensi formula injection di Excel
        if (in_array($str[0], ['=', '+', '-', '@'], true)) {
            $str = ltrim($str, "=+-@ \t\r\n");
        }

        return $str !== '' ? $str : null;
    }

    protected function parseDate(mixed $value): ?string
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
}
