<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use App\Exports\Templates\Sheets\BaseTemplateSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class OfficialMinistryTemplateExport implements WithMultipleSheets, Export
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Jabatan Pelayanan';
                }

                public function headings(): array
                {
                    return [
                        'nama_jabatan',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Pendeta Jemaat'],
                        ['Penatua'],
                        ['Diaken'],
                        ['Pemusik / Organis'],
                        ['Song Leader / Pemandu Pujian'],
                        ['Guru Sekolah Minggu'],
                        ['Operator Multimedia & Sound'],
                    ];
                }
            },
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Pejabat Gereja';
                }

                public function headings(): array
                {
                    return [
                        'tipe_pejabat',
                        'nik_anggota',
                        'nama_pejabat',
                        'gereja_asal',
                        'tanggal_mulai_jabatan',
                        'tanggal_selesai_jabatan',
                    ];
                }

                public function array(): array
                {
                    return [
                        [
                            'majelis_lokal',
                            '1801010101850001',
                            'Budi Santoso',
                            '',
                            '2024-01-01',
                            '',
                        ],
                        [
                            'pendeta_internal',
                            '',
                            'Pdt. Samuel Kriswanto, M.Th.',
                            '',
                            '2022-01-01',
                            '',
                        ],
                        [
                            'pelayan_tamu',
                            '',
                            'Pdt. Yohanes Wijaya, M.Div.',
                            'GKSBS Kotagajah',
                            '2025-06-01',
                            '2025-06-30',
                        ],
                    ];
                }
            },
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Petunjuk & Kamus Nilai';
                }

                public function headings(): array
                {
                    return [
                        'Sheet',
                        'Nama Kolom',
                        'Kewajiban',
                        'Pilihan Nilai / Format',
                        'Keterangan & Aturan Bisnis',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Jabatan Pelayanan', 'nama_jabatan', 'Wajib', 'Teks bebas', 'Nama fungsi/jabatan pelayanan rohani di gereja.'],
                        ['Pejabat Gereja', 'tipe_pejabat', 'Wajib', 'majelis_lokal, pendeta_internal, pelayan_tamu', 'Klasifikasi jenis pejabat rohani.'],
                        ['Pejabat Gereja', 'nik_anggota', 'Opsional', '16 digit angka', 'NIK jemaat lokal untuk ditautkan ke profil anggota (Wajib/Dianjurkan untuk majelis_lokal).'],
                        ['Pejabat Gereja', 'nama_pejabat', 'Wajib jika NIK kosong', 'Teks bebas', 'Nama pejabat gereja/tamu.'],
                        ['Pejabat Gereja', 'gereja_asal', 'Wajib jika pelayan tamu', 'Teks bebas', 'Nama gereja atau sinode asal bagi pelayan tamu.'],
                        ['Pejabat Gereja', 'tanggal_mulai_jabatan', 'Wajib', 'Tanggal (YYYY-MM-DD)', 'Tanggal penahbisan / awal masa bakti jabatan.'],
                        ['Pejabat Gereja', 'tanggal_selesai_jabatan', 'Opsional', 'Tanggal (YYYY-MM-DD)', 'Tanggal akhir masa jabatan. Kosongkan jika masih aktif menjabat.'],
                    ];
                }
            },
        ];
    }
}
