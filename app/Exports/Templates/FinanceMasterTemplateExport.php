<?php

declare(strict_types=1);

namespace App\Exports\Templates;

use App\Exports\Templates\Sheets\BaseTemplateSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

final class FinanceMasterTemplateExport implements WithMultipleSheets, Export
{
    use Exportable;

    public function sheets(): array
    {
        return [
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Pos Kas & Dana';
                }

                public function headings(): array
                {
                    return [
                        'nama_pos_dana',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Kas Umum Operasional'],
                        ['Dana Pembangunan Gedung'],
                        ['Dana Diakonia / Sosial'],
                        ['Kas Komisi Pemuda'],
                        ['Kas Komisi Wanita & Lansia'],
                    ];
                }
            },
            new class extends BaseTemplateSheet {
                public function title(): string
                {
                    return 'Kategori Transaksi';
                }

                public function headings(): array
                {
                    return [
                        'nama_kategori',
                        'tipe',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Kolekte Ibadah Hari Minggu', 'Pemasukan'],
                        ['Persembahan Persepuluhan', 'Pemasukan'],
                        ['Persembahan Syukur Khusus', 'Pemasukan'],
                        ['Biaya Listrik PLN & Air PAM', 'Pengeluaran'],
                        ['Bantuan Warga Sakit & Duka (Diakonia)', 'Pengeluaran'],
                        ['Operasional Kantor & Sekretariat', 'Pengeluaran'],
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
                        'Keterangan & Prinsip Tata Kelola',
                    ];
                }

                public function array(): array
                {
                    return [
                        ['Pos Kas & Dana', 'nama_pos_dana', 'Wajib', 'Teks bebas', 'Nama kantong/pos kas tunai mandiri (100% tunai per kantong). Tidak ada rekening/bank.'],
                        ['Kategori Transaksi', 'nama_kategori', 'Wajib', 'Teks bebas', 'Nama pos transaksi pemasukan atau pengeluaran.'],
                        ['Kategori Transaksi', 'tipe', 'Wajib', 'Pemasukan (Debit) ATAU Pengeluaran (Kredit)', 'Nilai dinormalisasi otomatis: "Pemasukan", "Masuk", "Debit", "debit" -> debit; "Pengeluaran", "Keluar", "Kredit", "credit" -> credit.'],
                    ];
                }
            },
        ];
    }
}
