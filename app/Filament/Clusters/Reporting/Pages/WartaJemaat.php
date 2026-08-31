<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\Event;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\Transaction;
use Illuminate\Support\Carbon;

class WartaJemaat extends BaseReportPage
{
    protected string $view = 'filament.pages.warta-jemaat';

    protected static ?string $navigationLabel = 'Warta Jemaat';

    protected static ?string $title = 'Warta Jemaat';

    protected static ?int $navigationSort = 1;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1: super_admin, church_admin, warta_editor.
        return ['super_admin', 'church_admin', 'warta_editor'];
    }

    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    public function mount(): void
    {
        parent::mount();

        $now = Carbon::now();
        $this->startDate = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $this->endDate = $now->copy()->endOfWeek(Carbon::SATURDAY);
    }

    protected function reportTitle(): string
    {
        return 'Warta-Jemaat-'.$this->startDate?->format('d-m-Y').'_'.$this->endDate?->format('d-m-Y');
    }

    public function getReportData(): array
    {
        $startDate = $this->startDate ?? Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endDate = $this->endDate ?? Carbon::now()->endOfWeek(Carbon::SATURDAY);

        // Scoping church_id dijamin global scope BelongsToChurch (T1) +
        // pemilih gereja super_admin (§9).

        $events = $this->scopeToActiveChurch(Event::with(['category', 'attendances', 'rosters' => function ($query) {
            $query->with(['member', 'official', 'role']);
        }]))
            ->whereBetween('start_datetime', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('start_datetime')
            ->get();

        $birthdays = $this->scopeToActiveChurch(Member::query())
            ->where('status', 'aktif')
            ->whereNotNull('birth_date')
            ->get()
            ->filter(function ($member) use ($startDate, $endDate) {
                $birthDate = Carbon::parse($member->birth_date);
                $thisYear = $birthDate->copy()->year(Carbon::now()->year);

                return $thisYear->between($startDate, $endDate);
            })
            ->sortBy(function ($member) {
                return Carbon::parse($member->birth_date)->dayOfYear;
            })
            ->values();

        $transactions = $this->scopeToActiveChurch(Transaction::query())
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get()
            ->groupBy(function ($transaction) {
                return $transaction->type === 'debit' ? 'Pemasukan' : 'Pengeluaran';
            });

        $sacraments = $this->scopeToActiveChurch(MemberSacrament::with(['member', 'official']))
            ->whereBetween('sacrament_date', [$startDate, $endDate])
            ->orderBy('sacrament_date')
            ->get();

        return [
            'churchName' => $this->activeChurchName(),
            'events' => $events,
            'birthdays' => $birthdays,
            'transactions' => $transactions,
            'sacraments' => $sacraments,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];
    }

    protected function exportBlocks(): array
    {
        $data = $this->getReportData();

        $blocks = [];

        // Jadwal Ibadah & Pelayanan
        $rows = $data['events']->map(fn (Event $event) => [
            $event->start_datetime?->format('d/m/Y H:i'),
            $event->name,
            $event->location,
            $event->rosters->map(fn ($r) => $r->member?->full_name ?? $r->official?->display_name)->filter()->implode(', '),
            (string) $event->total_attendance,
        ])->all();

        $blocks[] = [
            'title' => 'Jadwal Ibadah & Pelayanan',
            'headers' => ['Waktu', 'Acara', 'Lokasi', 'Petugas', 'Kehadiran'],
            'rows' => $rows,
        ];

        // Ulang Tahun
        $blocks[] = [
            'title' => 'Ulang Tahun Jemaat',
            'headers' => ['Nama', 'Tanggal'],
            'rows' => $data['birthdays']->map(fn ($m) => [
                $m->full_name,
                Carbon::parse($m->birth_date)->format('d/m/Y'),
            ])->all(),
        ];

        // Sakramen
        $blocks[] = [
            'title' => 'Perayaan Sakramen',
            'headers' => ['Tanggal', 'Jenis', 'Nama', 'Pelayan', 'No. Sertifikat'],
            'rows' => $data['sacraments']->map(fn ($s) => [
                $s->sacrament_date?->format('d/m/Y'),
                $s->type,
                $s->member?->full_name,
                $s->official?->display_name,
                $s->certificate_number,
            ])->all(),
        ];

        // Keuangan Ringkas
        $income = $data['transactions']->get('Pemasukan', collect());
        $expense = $data['transactions']->get('Pengeluaran', collect());
        $blocks[] = [
            'title' => 'Laporan Keuangan Ringkas',
            'headers' => ['Keterangan', 'Jumlah (Rp)'],
            'rows' => [
                ['Total Pemasukan', number_format($income->sum('amount'), 0, ',', '.')],
                ['Total Pengeluaran', number_format($expense->sum('amount'), 0, ',', '.')],
                ['Selisih', number_format($income->sum('amount') - $expense->sum('amount'), 0, ',', '.')],
            ],
        ];

        return $blocks;
    }
}
