<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use App\Exports\Templates\Sheets\BaseTemplateSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class MemberFamilyTemplateExport implements WithMultipleSheets, Export
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Data Jemaat';
                }

                public function headings(): array
                {
                    return [
                        'no_kk',
                        'nama_keluarga',
                        'alamat',
                        'nik',
                        'nama_lengkap',
                        'jenis_kelamin',
                        'tempat_lahir',
                        'tanggal_lahir',
                        'hubungan_keluarga',
                        'nomor_telepon',
                        'status_anggota',
                        'status_baptis',
                        'tanggal_baptis',
                        'nomor_surat_baptis',
                        'status_sidi',
                        'tanggal_sidi',
                        'nomor_surat_sidi',
                        'status_nikah',
                        'tanggal_nikah',
                        'nomor_surat_nikah',
                    ];
                }

                public function array(): array
                {
                    return [
                        [
                            'KK-001',
                            'Keluarga Budi Santoso',
                            'Jl. Melati No. 10 Candimas',
                            '1801010101850001',
                            'Budi Santoso',
                            'L',
                            'Lampung Tengah',
                            '1985-05-17',
                            'kepala_keluarga',
                            '081272001234',
                            'aktif',
                            'sudah',
                            '1985-08-20',
                            'BAP-1985-001',
                            'sudah',
                            '2002-04-14',
                            'SDI-2002-015',
                            'sudah',
                            '2010-09-12',
                            'NKH-2010-008',
                        ],
                        [
                            'KK-001',
                            'Keluarga Budi Santoso',
                            'Jl. Melati No. 10 Candimas',
                            '1801014101870002',
                            'Siti Aminah',
                            'P',
                            'Pesawaran',
                            '1987-11-23',
                            'istri',
                            '081272005678',
                            'aktif',
                            'sudah',
                            '1987-12-25',
                            'BAP-1987-042',
                            'sudah',
                            '2004-03-28',
                            'SDI-2004-022',
                            'sudah',
                            '2010-09-12',
                            'NKH-2010-008',
                        ],
                        [
                            'KK-001',
                            'Keluarga Budi Santoso',
                            'Jl. Melati No. 10 Candimas',
                            '1801012205150003',
                            'Daniel Santoso',
                            'L',
                            'Bandar Lampung',
                            '2015-05-22',
                            'anak',
                            '',
                            'aktif',
                            'sudah',
                            '2015-08-16',
                            'BAP-2015-030',
                            'belum',
                            '',
                            '',
                            'belum',
                            '',
                            '',
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
                        'Nama Kolom',
                        'Kewajiban',
                        'Format / Tipe Data',
                        'Pilihan Nilai Sah (Enum)',
                        'Keterangan & Aturan Bisnis',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['no_kk', 'Wajib', 'Teks / Angka', '-', 'Nomor Kartu Keluarga. Baris dengan nomor KK yang sama akan otomatis dikelompokkan ke satu entitas Keluarga.'],
                        ['nama_keluarga', 'Opsional', 'Teks', '-', 'Nama Keluarga (misal: "Keluarga Budi Santoso"). Jika kosong, diisi "Kel. [Nama Anggota Pertama]".'],
                        ['alamat', 'Wajib', 'Teks', '-', 'Alamat domisili keluarga.'],
                        ['nik', 'Opsional / Sangat Dianjurkan', 'Teks 16 digit', '-', 'Nomor Induk Kependudukan (No KTP). Digunakan untuk deduplikasi unik jemaat.'],
                        ['nama_lengkap', 'Wajib', 'Teks', '-', 'Nama lengkap jemaat tanpa gelar rohani.'],
                        ['jenis_kelamin', 'Wajib', 'Enum', 'L, P (atau Laki-laki, Perempuan)', 'Jenis kelamin jemaat.'],
                        ['tempat_lahir', 'Opsional', 'Teks', '-', 'Kota / tempat kelahiran.'],
                        ['tanggal_lahir', 'Opsional / Dianjurkan', 'Tanggal (YYYY-MM-DD)', 'Contoh: 1985-05-17 (atau 17/05/1985)', 'Tanggal lahir jemaat.'],
                        ['hubungan_keluarga', 'Wajib', 'Enum', 'kepala_keluarga, istri, anak, lainnya', 'Posisi hubungan dalam susunan keluarga.'],
                        ['nomor_telepon', 'Opsional', 'Teks / Angka', 'Contoh: 081272001234', 'Nomor HP / WhatsApp jemaat.'],
                        ['status_anggota', 'Wajib', 'Enum', 'aktif, titipan, pindah, meninggal', 'Default: aktif.'],
                        ['status_baptis', 'Opsional', 'Enum', 'sudah, belum', 'Jika "sudah" atau tanggal_baptis terisi, otomatis dibuatkan record sakramen Baptis (Anak/Dewasa berdasarkan usia).'],
                        ['tanggal_baptis', 'Opsional', 'Tanggal (YYYY-MM-DD)', 'Contoh: 1985-08-20', 'Tanggal pelaksanaan sakramen baptis.'],
                        ['nomor_surat_baptis', 'Opsional', 'Teks', '-', 'Nomor surat / sertifikat baptis jika ada.'],
                        ['status_sidi', 'Opsional', 'Enum', 'sudah, belum', 'Jika "sudah" atau tanggal_sidi terisi, otomatis dibuatkan record sakramen Sidi.'],
                        ['tanggal_sidi', 'Opsional', 'Tanggal (YYYY-MM-DD)', 'Contoh: 2002-04-14', 'Tanggal peneguhan sidi.'],
                        ['nomor_surat_sidi', 'Opsional', 'Teks', '-', 'Nomor piagam sidi jika ada.'],
                        ['status_nikah', 'Opsional', 'Enum', 'sudah, belum', 'Jika "sudah" atau tanggal_nikah terisi, otomatis dibuatkan record sakramen Pernikahan.'],
                        ['tanggal_nikah', 'Opsional', 'Tanggal (YYYY-MM-DD)', 'Contoh: 2010-09-12', 'Tanggal pemberkatan nikah.'],
                        ['nomor_surat_nikah', 'Opsional', 'Teks', '-', 'Nomor akta pernikahan gereja jika ada.'],
                    ];
                }
            },
        ];
    }
}
