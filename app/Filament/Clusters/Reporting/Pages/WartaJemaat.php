<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Pages;

use App\Models\Church;
use App\Models\Event;
use App\Models\Fund;
use App\Models\Member;
use App\Models\MemberSacrament;
use App\Models\Transaction;
use App\Models\WartaPublication;
use BackedEnum;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

class WartaJemaat extends BaseReportPage
{
    protected string $view = 'filament.pages.warta-jemaat';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = 'Warta Jemaat';

    protected static ?string $title = 'Warta Jemaat';

    protected static ?int $navigationSort = 1;

    protected static function allowedRoles(): array
    {
        // Matriks §1.1 + spec T3: super_admin, church_admin, warta_editor, report_viewer (read-only).
        return ['super_admin', 'church_admin', 'warta_editor', 'report_viewer'];
    }

    public ?Carbon $startDate = null;

    public ?Carbon $endDate = null;

    public ?string $reflection = null;

    public function mount(): void
    {
        parent::mount();

        // Default: minggu berjalan (Senin–Minggu). startOfWeek(SUNDAY) dipakai agar
        // konsisten dengan test WartaJemaatTest (weekStart = startOfWeek(Carbon::SUNDAY)).
        $now = Carbon::now();
        $this->startDate = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $this->endDate = $now->copy()->endOfWeek(Carbon::SATURDAY);

        $this->loadExistingReflection();
    }

    public function updatedStartDate(mixed $value = null): void
    {
        if (is_string($value) && ! empty($value)) {
            $this->startDate = Carbon::parse($value);
        }

        $this->loadExistingReflection();
    }

    public function updatedEndDate(mixed $value = null): void
    {
        if (is_string($value) && ! empty($value)) {
            $this->endDate = Carbon::parse($value);
        }

        $this->loadExistingReflection();
    }

    public function updatedChurchSelect(int|string|null $value): void
    {
        parent::updatedChurchSelect($value);

        $this->loadExistingReflection();
    }

    /**
     * Preset periode untuk UX filter cepat.
     */
    public function setPeriod(string $period): void
    {
        $now = Carbon::now();

        match ($period) {
            'thisWeek' => $this->setWeekRange($now),
            'lastWeek' => $this->setWeekRange($now->copy()->subWeek()),
            'thisMonth' => $this->setMonthRange($now),
            default => $this->setWeekRange($now),
        };

        $this->loadExistingReflection();
    }

    /**
     * Geser rentang seminggu (prev/next) dari tanggal mulai sekarang.
     */
    public function shiftWeek(int $weeks): void
    {
        $base = $this->startDate?->copy()->addWeeks($weeks) ?? Carbon::now();
        $this->setWeekRange($base);

        $this->loadExistingReflection();
    }

    /**
     * Muat renungan yang sudah tersimpan untuk edisi & gereja yang aktif jika ada.
     */
    public function loadExistingReflection(): void
    {
        $publication = $this->getActivePublication() ?? $this->getPublication();

        $this->reflection = $publication
            ? ($publication->content['reflection'] ?? $publication->content['renungan'] ?? null)
            : null;
    }

    /**
     * Mengambil publikasi untuk periode dan gereja saat ini (baik draft maupun published).
     */
    public function getPublication(): ?WartaPublication
    {
        $churchId = $this->activeChurchId() ?? auth()->user()?->church_id;
        if (! $churchId || ! $this->startDate || ! $this->endDate) {
            return null;
        }

        $startDateStr = $this->startDate instanceof Carbon
            ? $this->startDate->toDateString()
            : Carbon::parse((string) $this->startDate)->toDateString();

        $endDateStr = $this->endDate instanceof Carbon
            ? $this->endDate->toDateString()
            : Carbon::parse((string) $this->endDate)->toDateString();

        return WartaPublication::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->with('church')
            ->where('church_id', $churchId)
            ->whereDate('period_start', $startDateStr)
            ->whereDate('period_end', $endDateStr)
            ->latest('updated_at')
            ->first();
    }

    /**
     * Mengambil publikasi aktif untuk periode dan gereja saat ini.
     */
    public function getActivePublication(): ?WartaPublication
    {
        $churchId = $this->activeChurchId() ?? auth()->user()?->church_id;
        if (! $churchId || ! $this->startDate || ! $this->endDate) {
            return null;
        }

        $startDateStr = $this->startDate instanceof Carbon
            ? $this->startDate->toDateString()
            : Carbon::parse((string) $this->startDate)->toDateString();

        $endDateStr = $this->endDate instanceof Carbon
            ? $this->endDate->toDateString()
            : Carbon::parse((string) $this->endDate)->toDateString();

        return WartaPublication::query()
            ->withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('status', 'published')
            ->with('church')
            ->where('church_id', $churchId)
            ->whereDate('period_start', $startDateStr)
            ->whereDate('period_end', $endDateStr)
            ->latest('published_at')
            ->first();
    }

    /**
     * URL publik warta untuk edisi yang sudah dipublikasikan.
     */
    public function getActivePublicationUrl(): ?string
    {
        $publication = $this->getActivePublication();
        if (! $publication) {
            return null;
        }

        $church = $publication->church ?? $this->activeChurchModel();
        $churchCode = $church?->code;
        if (! $churchCode) {
            return null;
        }

        return route('public.warta.show', [
            'church' => $churchCode,
            'publication' => $publication->id,
        ]);
    }

    /**
     * Hak akses penerbitan warta (super_admin, church_admin, warta_editor).
     */
    public function canPublishWarta(): bool
    {
        $user = auth()->user();

        return $user !== null && in_array($user->role, ['super_admin', 'church_admin', 'warta_editor'], true);
    }

    /**
     * Publikasikan warta jemaat dan renungan ke portal publik.
     */
    public function publishWarta(): void
    {
        $user = auth()->user();
        abort_unless(
            $user !== null && in_array($user->role, ['super_admin', 'church_admin', 'warta_editor'], true),
            403,
            'Tidak diizinkan mempublikasikan warta.'
        );

        $churchId = $this->activeChurchId() ?? $user->church_id;
        if (! $churchId) {
            Notification::make()
                ->title('Pilih Gereja Terlebih Dahulu')
                ->body('Silakan pilih salah satu gereja sebelum mempublikasikan warta jemaat.')
                ->warning()
                ->send();

            return;
        }

        $startDate = $this->startDate instanceof Carbon
            ? $this->startDate
            : Carbon::parse((string) ($this->startDate ?? Carbon::now()->startOfWeek(Carbon::SUNDAY)));

        $endDate = $this->endDate instanceof Carbon
            ? $this->endDate
            : Carbon::parse((string) ($this->endDate ?? Carbon::now()->endOfWeek(Carbon::SATURDAY)));

        $this->startDate = $startDate;
        $this->endDate = $endDate;

        $targetChurch = Church::query()->withoutGlobalScopes()->find($churchId);
        $data = $this->getReportData();

        $snapshot = $this->buildSnapshot($data, $targetChurch);
        $title = $data['periodLabel'] ?? ('Warta '.$startDate->format('d-m-Y'));

        $publication = WartaPublication::withoutGlobalScopes()->withTrashed()->updateOrCreate(
            [
                'church_id' => $churchId,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
            ],
            [
                'title' => $title,
                'content' => $snapshot,
                'status' => 'published',
                'published_at' => now(),
                'created_by' => $user->id,
            ]
        );

        if ($publication->trashed()) {
            $publication->restore();
        }

        Notification::make()
            ->title('Warta Jemaat & Renungan Berhasil Dipublikasikan')
            ->success()
            ->send();
    }

    /**
     * Batalkan / tarik publikasi warta untuk periode dan gereja terpilih.
     */
    public function rollbackWarta(): void
    {
        abort_unless($this->canPublishWarta(), 403, 'Tidak diizinkan menarik publikasi warta.');

        $publication = $this->getActivePublication() ?? $this->getPublication();

        if (! $publication) {
            Notification::make()
                ->title('Warta Tidak Ditemukan')
                ->body('Tidak ada warta aktif yang dapat ditarik untuk periode ini.')
                ->warning()
                ->send();

            return;
        }

        $currentReflection = $this->reflection ?: ($publication->content['reflection'] ?? $publication->content['renungan'] ?? null);

        $content = $publication->content ?? [];
        if (is_array($content)) {
            $content['reflection'] = $currentReflection;
            $content['renungan'] = $currentReflection;
        }

        $publication->update([
            'status' => 'draft',
            'published_at' => null,
            'content' => $content,
        ]);

        $this->reflection = $currentReflection;

        Notification::make()
            ->title('Publikasi Warta Dibatalkan')
            ->body('Warta telah ditarik dari portal publik & jemaat. Anda dapat mengedit renungan atau data warta, lalu mempublikasikannya kembali.')
            ->success()
            ->send();
    }

    /**
     * Susun snapshot konten lengkap warta untuk portal jemaat & publik.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function buildSnapshot(array $data, ?Church $church = null): array
    {
        $church ??= ($data['church'] ?? $this->activeChurchModel());

        $events = collect($data['events'] ?? [])->map(fn ($event) => [
            'name' => $event->name ?? $event->title ?? 'Ibadah',
            'start' => optional($event->start_datetime)->format('d/m/Y H:i'),
            'location' => $event->location ?? '',
            'officials' => collect($event->rosters ?? [])
                ->map(fn ($r) => $r->member?->full_name ?? $r->official?->display_name)
                ->filter()
                ->implode(', '),
        ])->all();

        $birthdays = collect($data['birthdays'] ?? [])->map(fn ($m) => [
            'name' => $m->full_name ?? $m->name,
            'date' => optional($m->birth_date)->format('d/m/Y'),
        ])->all();

        $sacraments = collect($data['sacraments'] ?? [])->map(fn ($s) => [
            'date' => optional($s->sacrament_date)->format('d/m/Y'),
            'type' => $s->type,
            'name' => $s->member?->full_name ?? '',
            'official' => $s->official?->display_name ?? '',
        ])->all();

        return [
            'church' => [
                'name' => $church?->name ?? $data['churchName'] ?? 'Gereja',
                'synod' => $church?->synod,
                'address' => $church?->address ?? $data['churchAddress'] ?? '',
                'phone' => $church?->phone,
                'email' => $church?->email,
                'logo_url' => $church?->logo_url,
            ],
            'church_name' => $church?->name ?? $data['churchName'] ?? 'Gereja',
            'church_address' => $church?->address ?? $data['churchAddress'] ?? '',
            'period_label' => $data['periodLabel'] ?? null,
            'edition_label' => $data['editionLabel'] ?? null,
            'reflection' => $this->reflection,
            'renungan' => $this->reflection,
            'events' => $events,
            'birthdays' => $birthdays,
            'sacraments' => $sacraments,
            'finance' => [
                'opening_balance' => (int) ($data['openingBalance'] ?? 0),
                'total_income' => (int) ($data['totalIncome'] ?? 0),
                'total_expenses' => (int) ($data['totalExpenses'] ?? 0),
                'closing_balance' => (int) ($data['closingBalance'] ?? 0),
                'funds' => $data['fundsReport'] ?? $data['fundBreakdowns'] ?? $data['funds_report'] ?? [],
            ],
        ];
    }

    private function setWeekRange(Carbon $anchor): void
    {
        $this->startDate = $anchor->copy()->startOfWeek(Carbon::SUNDAY);
        $this->endDate = $anchor->copy()->endOfWeek(Carbon::SATURDAY);
    }

    private function setMonthRange(Carbon $anchor): void
    {
        $this->startDate = $anchor->copy()->startOfMonth();
        $this->endDate = $anchor->copy()->endOfMonth();
    }

    /**
     * Ketersediaan endpoint export PDF (disediakan backend, T1 Byte).
     * Guard Route::has() — kalau route belum terdaftar, tombol dirender DISABLED
     * dan route() tidak pernah dipanggil (Vera HIGH + MED).
     */
    public function canExportPdf(): bool
    {
        return Route::has('warta-jemaat.export-pdf');
    }

    /**
     * Ketersediaan endpoint export Excel (disediakan backend, T1 Byte).
     */
    public function canExportExcel(): bool
    {
        return Route::has('warta-jemaat.export-excel');
    }

    protected function reportTitle(): string
    {
        return 'Warta-Jemaat-'.$this->startDate?->format('d-m-Y').'_'.$this->endDate?->format('d-m-Y');
    }

    /**
     * Data tunggal untuk tampilan & export (AC-3A-02: single source).
     *
     * @return array<string, mixed>
     */
    public function getReportData(): array
    {
        $startDate = $this->startDate ?? Carbon::now()->startOfWeek(Carbon::SUNDAY);
        $endDate = $this->endDate ?? Carbon::now()->endOfWeek(Carbon::SATURDAY);

        // Scoping church_id dijamin global scope BelongsToChurch (T1) +
        // pemilih gereja super_admin (§9) via scopeToActiveChurch().

        // Agenda / jadwal ibadah + pelayanan. Eager-load attendances supaya
        // $event->total_attendance memakai relasi (MED-2 Vera, tanpa N+1).
        $events = $this->scopeToActiveChurch(Event::with([
            'category',
            'attendances',
            'rosters' => function ($query) {
                $query->with(['member', 'official', 'role']);
            },
        ]))
            ->whereBetween('start_datetime', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->orderBy('start_datetime')
            ->get();

        // Ulang tahun dalam rentang periode (status aktif, null-safe).
        $birthdays = $this->scopeToActiveChurch(Member::query())
            ->where('status', 'aktif')
            ->whereNotNull('birth_date')
            ->get()
            ->filter(function ($member) use ($startDate, $endDate) {
                if (blank($member->birth_date)) {
                    return false;
                }

                $birthDate = Carbon::parse($member->birth_date);
                $thisYear = $birthDate->copy()->year(Carbon::now()->year);

                return $thisYear->between($startDate, $endDate);
            })
            ->sortBy(function ($member) {
                return blank($member->birth_date)
                    ? 9999
                    : Carbon::parse($member->birth_date)->dayOfYear;
            })
            ->values();

        // Sakramen / berita jemaat periode.
        $sacraments = $this->scopeToActiveChurch(MemberSacrament::with(['member', 'official']))
            ->whereBetween('sacrament_date', [$startDate, $endDate])
            ->orderBy('sacrament_date')
            ->get();

        // -------------------------------------------------------------
        // Perhitungan Keuangan Kas Tunai per Kantong (Fund)
        // -------------------------------------------------------------
        $funds = $this->scopeToActiveChurch(Fund::query())->orderBy('name')->get();

        // Single query saldo awal per fund sebelum $startDate
        $openingBalancesQuery = $this->scopeToActiveChurch(Transaction::query())
            ->whereDate('transaction_date', '<', $startDate);

        if ($funds->isNotEmpty()) {
            $openingBalancesQuery->whereIn('fund_id', $funds->pluck('id'));
        }

        $openingBalances = $openingBalancesQuery
            ->selectRaw('fund_id, type, SUM(amount) as total')
            ->groupBy('fund_id', 'type')
            ->get()
            ->groupBy('fund_id')
            ->map(function ($items) {
                $debit = (int) ($items->firstWhere('type', 'debit')?->total ?? 0);
                $credit = (int) ($items->firstWhere('type', 'credit')?->total ?? 0);

                return $debit - $credit;
            });

        // Transaksi periode warta
        $periodTransactions = $this->scopeToActiveChurch(Transaction::with(['fund', 'category']))
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->get();

        // Transaksi periode dikelompokkan legacy (Pemasukan / Pengeluaran)
        $transactions = $periodTransactions->groupBy(function ($transaction) {
            return $transaction->type === 'debit' ? 'Pemasukan' : 'Pengeluaran';
        });

        $fundBreakdowns = [];
        $consolidatedOpening = 0;
        $consolidatedIncome = 0;
        $consolidatedExpenses = 0;

        foreach ($funds as $fund) {
            $fundTxns = $periodTransactions->where('fund_id', $fund->id);
            $opening = (int) ($openingBalances[$fund->id] ?? 0);

            // Grouping Pemasukan (debit) per kategori
            $incomeTxns = $fundTxns->where('type', 'debit');
            $incomeItems = $incomeTxns->groupBy(fn ($t) => $t->category?->name ?? 'Pemasukan Lain-lain')
                ->map(fn ($group, $catName) => [
                    'category' => $catName,
                    'amount' => (int) $group->sum('amount'),
                ])->values()->all();
            $fundTotalIncome = (int) $incomeTxns->sum('amount');

            // Grouping Pengeluaran (credit) per kategori
            $expenseTxns = $fundTxns->where('type', 'credit');
            $expenseItems = $expenseTxns->groupBy(fn ($t) => $t->category?->name ?? 'Pengeluaran Lain-lain')
                ->map(fn ($group, $catName) => [
                    'category' => $catName,
                    'amount' => (int) $group->sum('amount'),
                ])->values()->all();
            $fundTotalExpense = (int) $expenseTxns->sum('amount');

            $closing = $opening + $fundTotalIncome - $fundTotalExpense;

            $fundBreakdowns[] = [
                'id' => $fund->id,
                'name' => $fund->name,
                'opening_balance' => $opening,
                'income' => [
                    'total' => $fundTotalIncome,
                    'items' => $incomeItems,
                ],
                'expense' => [
                    'total' => $fundTotalExpense,
                    'items' => $expenseItems,
                ],
                'closing_balance' => $closing,
            ];

            $consolidatedOpening += $opening;
            $consolidatedIncome += $fundTotalIncome;
            $consolidatedExpenses += $fundTotalExpense;
        }

        // Penanganan fallback jika ada transaksi tanpa fund_id atau dana belum dibuat
        $rawOpening = $this->getOpeningBalance($startDate);
        $rawIncome = (int) ($transactions->get('Pemasukan')?->sum('amount') ?? 0);
        $rawExpense = (int) ($transactions->get('Pengeluaran')?->sum('amount') ?? 0);

        if ($funds->isEmpty()) {
            $consolidatedOpening = $rawOpening;
            $consolidatedIncome = $rawIncome;
            $consolidatedExpenses = $rawExpense;
        } else {
            $unassignedIncome = $rawIncome - $consolidatedIncome;
            $unassignedExpense = $rawExpense - $consolidatedExpenses;
            $unassignedOpening = $rawOpening - $consolidatedOpening;

            if ($unassignedOpening !== 0 || $unassignedIncome > 0 || $unassignedExpense > 0) {
                $unassignedTxns = $periodTransactions->whereNull('fund_id');
                $unassignedDebit = $unassignedTxns->where('type', 'debit');
                $unassignedCredit = $unassignedTxns->where('type', 'credit');

                $fundBreakdowns[] = [
                    'id' => null,
                    'name' => 'Pos Kas Lainnya',
                    'opening_balance' => $unassignedOpening,
                    'income' => [
                        'total' => $unassignedIncome,
                        'items' => $unassignedDebit->groupBy(fn ($t) => $t->category?->name ?? 'Lain-lain')
                            ->map(fn ($group, $name) => ['category' => $name, 'amount' => (int) $group->sum('amount')])
                            ->values()->all(),
                    ],
                    'expense' => [
                        'total' => $unassignedExpense,
                        'items' => $unassignedCredit->groupBy(fn ($t) => $t->category?->name ?? 'Lain-lain')
                            ->map(fn ($group, $name) => ['category' => $name, 'amount' => (int) $group->sum('amount')])
                            ->values()->all(),
                    ],
                    'closing_balance' => $unassignedOpening + $unassignedIncome - $unassignedExpense,
                ];

                $consolidatedOpening += $unassignedOpening;
                $consolidatedIncome += $unassignedIncome;
                $consolidatedExpenses += $unassignedExpense;
            }
        }

        $consolidatedClosing = $consolidatedOpening + $consolidatedIncome - $consolidatedExpenses;

        return [
            'events' => $events,
            'birthdays' => $birthdays,
            'transactions' => $transactions,
            'sacraments' => $sacraments,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'church' => $this->activeChurchModel(),
            'churchName' => $this->activeChurchName(),
            'churchAddress' => $this->getChurchAddress(),
            'openingBalance' => $consolidatedOpening,
            'totalIncome' => $consolidatedIncome,
            'totalExpenses' => $consolidatedExpenses,
            'closingBalance' => $consolidatedClosing,
            'fundBreakdowns' => $fundBreakdowns,
            'funds_report' => $fundBreakdowns,
            'fundsReport' => $fundBreakdowns,
            'periodLabel' => $this->formatPeriodLabel($startDate, $endDate),
            'editionLabel' => $this->formatEditionLabel($startDate),
            'reflection' => $this->reflection,
            'renungan' => $this->reflection,
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

        // Keuangan Ringkas (Konsolidasi Global)
        $blocks[] = [
            'title' => 'Laporan Keuangan Ringkas',
            'headers' => ['Keterangan', 'Jumlah (Rp)'],
            'rows' => [
                ['Total Saldo Awal', number_format($data['openingBalance'], 0, ',', '.')],
                ['Total Pemasukan', number_format($data['totalIncome'], 0, ',', '.')],
                ['Total Pengeluaran', number_format($data['totalExpenses'], 0, ',', '.')],
                ['Total Saldo Akhir', number_format($data['closingBalance'], 0, ',', '.')],
            ],
            'options' => ['totalRows' => 1, 'currencyColumns' => [2]],
        ];

        // Rincian Kas Tunai per Kantong
        if (! empty($data['fundBreakdowns'])) {
            $fundRows = [];
            foreach ($data['fundBreakdowns'] as $fund) {
                $fundRows[] = [
                    $fund['name'],
                    number_format($fund['opening_balance'], 0, ',', '.'),
                    number_format($fund['income']['total'], 0, ',', '.'),
                    number_format($fund['expense']['total'], 0, ',', '.'),
                    number_format($fund['closing_balance'], 0, ',', '.'),
                ];
            }

            $blocks[] = [
                'title' => 'Laporan Arus Kas Tunai per Kantong',
                'headers' => ['Nama Kantong / Pos Dana', 'Saldo Awal (Rp)', 'Pemasukan (Rp)', 'Pengeluaran (Rp)', 'Saldo Akhir (Rp)'],
                'rows' => $fundRows,
                'options' => ['currencyColumns' => [2, 3, 4, 5]],
            ];
        }

        return $blocks;
    }

    /**
     * Alamat gereja pada kop warta (opsional).
     */
    private function getChurchAddress(): string
    {
        $church = $this->activeChurchModel();
        if ($church && ! empty($church->address)) {
            return $church->address;
        }

        $user = auth()->user();

        if (! $user || $user->role === 'super_admin') {
            return '';
        }

        return $user->church?->address ?? '';
    }

    /**
     * Saldo awal = total debit - total credit sebelum tanggal mulai.
     */
    private function getOpeningBalance(Carbon $startDate): int
    {
        $debit = $this->scopeToActiveChurch(Transaction::query())
            ->where('type', 'debit')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        $credit = $this->scopeToActiveChurch(Transaction::query())
            ->where('type', 'credit')
            ->whereDate('transaction_date', '<', $startDate)
            ->sum('amount');

        return (int) $debit - (int) $credit;
    }

    /**
     * Label periode, mis. "9–15 Maret 2026".
     */
    private function formatPeriodLabel(Carbon $startDate, Carbon $endDate): string
    {
        $start = $startDate->locale('id')->isoFormat('D MMMM YYYY');
        $end = $endDate->locale('id')->isoFormat('D MMMM YYYY');

        return "{$start} – {$end}";
    }

    /**
     * Label edisi, mis. "Edisi Minggu ke-10 — 2026".
     */
    private function formatEditionLabel(Carbon $startDate): string
    {
        return 'Edisi Minggu ke-'.$startDate->weekOfYear.' — '.$startDate->year;
    }
}
