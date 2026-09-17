<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use App\Exports\Templates\Sheets\BaseTemplateSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class EventScheduleTemplateExport implements WithMultipleSheets, Export
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Kategori Acara';
                }

                public function headings(): array
                {
                    return [
                        'nama_kategori',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Ibadah Hari Minggu'],
                        ['Persekutuan Doa'],
                        ['Kebaktian Rumah Tangga'],
                        ['Ibadah Pemuda & Remaja'],
                        ['Sekolah Minggu Anak'],
                        ['Rapat Majelis Jemaat'],
                    ];
                }
            },
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Jadwal Berulang';
                }

                public function headings(): array
                {
                    return [
                        'kategori',
                        'judul_jadwal',
                        'lokasi',
                        'frekuensi',
                        'hari_pelaksanaan',
                        'jam_mulai',
                        'jam_selesai',
                        'tanggal_mulai_berlaku',
                        'keterangan',
                    ];
                }

                public function array(): array
                {
                    return [
                        [
                            'Ibadah Hari Minggu',
                            'Ibadah Raya Minggu Induk',
                            'Gedung Gereja Utama',
                            'weekly',
                            'Minggu',
                            '08:30',
                            '10:30',
                            '2026-01-01',
                            'Ibadah tatap muka jemaat induk',
                        ],
                        [
                            'Sekolah Minggu Anak',
                            'Kebaktian Sekolah Minggu',
                            'Gedung Serbaguna',
                            'weekly',
                            'Minggu',
                            '08:30',
                            '10:00',
                            '2026-01-01',
                            'Kelas Batita, Pratama, Madya',
                        ],
                        [
                            'Persekutuan Doa',
                            'Doa & PA Tengah Minggu',
                            'Gedung Gereja Utama',
                            'weekly',
                            'Rabu',
                            '18:30',
                            '20:00',
                            '2026-01-01',
                            'Pendalaman Alkitab seluruh warga',
                        ],
                        [
                            'Ibadah Pemuda & Remaja',
                            'Ibadah Pemuda Filadelfia',
                            'Ruang Pemuda',
                            'weekly',
                            'Sabtu',
                            '17:00',
                            '19:00',
                            '2026-01-01',
                            'Pujian dan penyembahan pemuda',
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
                        ['Kategori Acara', 'nama_kategori', 'Wajib', 'Teks bebas', 'Master kategori agenda dan ibadah gereja.'],
                        ['Jadwal Berulang', 'kategori', 'Wajib', 'Teks bebas', 'Kategori acara. Jika kategori belum ada di database, sistem akan otomatis membuatnya.'],
                        ['Jadwal Berulang', 'judul_jadwal', 'Wajib', 'Teks bebas', 'Judul/nama jadwal ibadah rutin.'],
                        ['Jadwal Berulang', 'lokasi', 'Wajib', 'Teks bebas', 'Ruang gereja, aula, atau rumah jemaat.'],
                        ['Jadwal Berulang', 'frekuensi', 'Wajib', 'weekly, monthly, daily (atau mingguan, bulanan, harian)', 'Pola pengulangan acara.'],
                        ['Jadwal Berulang', 'hari_pelaksanaan', 'Wajib untuk mingguan', 'Minggu, Senin, Selasa, Rabu, Kamis, Jumat, Sabtu (atau 0-6)', 'Hari pelaksanaan acara.'],
                        ['Jadwal Berulang', 'jam_mulai', 'Wajib', 'Format HH:MM (misal: 08:30)', 'Jam mulai kegiatan.'],
                        ['Jadwal Berulang', 'jam_selesai', 'Wajib', 'Format HH:MM (misal: 10:30)', 'Jam berakhirnya kegiatan.'],
                        ['Jadwal Berulang', 'tanggal_mulai_berlaku', 'Wajib', 'Tanggal (YYYY-MM-DD)', 'Tanggal awal berlakunya jadwal berulang.'],
                        ['Jadwal Berulang', 'keterangan', 'Opsional', 'Teks bebas', 'Catatan perlengkapan, tema, atau instruksi pastoral.'],
                    ];
                }
            },
        ];
    }
}
