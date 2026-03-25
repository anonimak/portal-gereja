<x-filament-panels::page>

    @php
        $reportData = $this->reportData;
    @endphp

    @push('styles')
        <style>
            @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

            .laporan-wrap * {
                font-family: 'Plus Jakarta Sans', sans-serif;
            }

            /* ── Card base ── */
            .card {
                background: #ffffff;
                border: 1px solid #e9ecef;
                border-radius: 16px;
                overflow: hidden;
            }

            /* ── Section header ── */
            .section-header {
                padding: 20px 28px;
                border-bottom: 1px solid #e9ecef;
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .section-header-icon {
                width: 36px;
                height: 36px;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .section-header h2 {
                font-size: 15px;
                font-weight: 700;
                color: #111827;
                margin: 0;
            }

            .section-header p {
                font-size: 12px;
                color: #9ca3af;
                margin: 2px 0 0;
            }

            /* ── Filter card ── */
            .filter-card {
                background: #ffffff;
                border: 1px solid #e9ecef;
                border-radius: 16px;
                padding: 24px 28px;
                margin-bottom: 24px;
            }

            .filter-label {
                font-size: 11px;
                font-weight: 700;
                color: #6b7280;
                letter-spacing: 0.08em;
                text-transform: uppercase;
                margin-bottom: 12px;
            }

            /* ── Export buttons ── */
            .btn-pdf {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 10px 18px;
                background: #1e40af;
                color: #ffffff;
                font-size: 13px;
                font-weight: 600;
                border-radius: 10px;
                border: none;
                cursor: pointer;
                transition: background 0.15s;
                text-decoration: none;
            }

            .btn-pdf:hover {
                background: #1d3a9e;
            }

            .btn-excel {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                width: 100%;
                padding: 10px 18px;
                background: #15803d;
                color: #ffffff;
                font-size: 13px;
                font-weight: 600;
                border-radius: 10px;
                border: 1px solid #15803d;
                cursor: pointer;
                transition: background 0.15s;
                text-decoration: none;
            }

            .btn-excel:hover {
                background: #166534;
            }

            /* ── Table ── */
            .report-table {
                width: 100%;
                border-collapse: collapse;
            }

            .report-table thead tr {
                background: #f8fafc;
            }

            .report-table th {
                padding: 12px 20px;
                font-size: 11px;
                font-weight: 700;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.07em;
                text-align: left;
                border-bottom: 1px solid #e9ecef;
                white-space: nowrap;
            }

            .report-table th.center {
                text-align: center;
            }

            .report-table td {
                padding: 14px 20px;
                font-size: 13px;
                color: #374151;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: top;
            }

            .report-table tbody tr:last-child td {
                border-bottom: none;
            }

            .report-table tbody tr:hover td {
                background: #fafbfc;
            }

            .td-center {
                text-align: center;
            }

            .badge-cat {
                display: inline-block;
                padding: 2px 10px;
                background: #eff6ff;
                color: #1d4ed8;
                font-size: 11px;
                font-weight: 600;
                border-radius: 20px;
            }

            .roster-row {
                font-size: 11.5px;
                color: #6b7280;
                margin-bottom: 3px;
                line-height: 1.4;
            }

            .roster-row span {
                color: #374151;
                font-weight: 600;
            }

            .attendance-num {
                font-size: 14px;
                font-weight: 700;
            }

            .attendance-total {
                font-size: 14px;
                font-weight: 800;
                color: #1d4ed8;
            }

            /* ── Finance Summary ── */
            .fin-summary {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                border-bottom: 1px solid #e9ecef;
            }

            .fin-summary-item {
                padding: 24px;
                text-align: center;
                border-right: 1px solid #e9ecef;
            }

            .fin-summary-item:last-child {
                border-right: none;
            }

            .fin-label {
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.1em;
                text-transform: uppercase;
                color: #9ca3af;
                margin-bottom: 8px;
            }

            .fin-amount {
                font-size: 22px;
                font-weight: 800;
                line-height: 1;
            }

            .fin-amount.blue {
                color: #1d4ed8;
            }

            .fin-amount.green {
                color: #15803d;
            }

            .fin-amount.red {
                color: #dc2626;
            }

            /* ── Finance Tables ── */
            .fin-tables {
                display: grid;
                grid-template-columns: 1fr 1fr;
                border-bottom: 1px solid #e9ecef;
            }

            .fin-col {
                overflow: hidden;
            }

            .fin-col:first-child {
                border-right: 1px solid #e9ecef;
            }

            .fin-col-header {
                padding: 14px 20px;
                font-size: 12px;
                font-weight: 700;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                border-bottom: 1px solid #e9ecef;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .fin-col-header.green-h {
                background: #f0fdf4;
                color: #15803d;
            }

            .fin-col-header.red-h {
                background: #fff5f5;
                color: #dc2626;
            }

            .fin-row {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 11px 20px;
                border-bottom: 1px solid #f1f5f9;
                font-size: 13px;
            }

            .fin-row:last-child {
                border-bottom: none;
            }

            .fin-row-cat {
                color: #6b7280;
            }

            .fin-row-amt {
                font-weight: 700;
                color: #111827;
            }

            /* ── Closing Balance ── */
            .closing-bal {
                padding: 28px;
                text-align: center;
            }

            .closing-label {
                font-size: 10px;
                font-weight: 700;
                letter-spacing: 0.12em;
                text-transform: uppercase;
                color: #9ca3af;
                margin-bottom: 10px;
            }

            .closing-amount {
                font-size: 32px;
                font-weight: 800;
                line-height: 1;
            }

            .closing-amount.surplus {
                color: #15803d;
            }

            .closing-amount.deficit {
                color: #dc2626;
            }

            .closing-badge {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                margin-top: 10px;
                padding: 4px 14px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }

            .closing-badge.surplus {
                background: #dcfce7;
                color: #15803d;
            }

            .closing-badge.deficit {
                background: #fee2e2;
                color: #dc2626;
            }

            /* ── Empty state ── */
            .empty-state {
                padding: 48px 24px;
                text-align: center;
                color: #9ca3af;
                font-size: 13px;
            }

            .empty-state svg {
                margin: 0 auto 12px;
                opacity: .35;
            }

            /* ── Divider ── */
            .section-gap {
                margin-top: 20px;
            }

            /* ── Print ── */
            @media print {
                .print-hidden {
                    display: none !important;
                }

                .print-header {
                    display: block !important;
                }

                .laporan-wrap {
                    padding: 0;
                }

                .card,
                .filter-card {
                    border: 1px solid #d1d5db !important;
                    border-radius: 8px !important;
                    box-shadow: none !important;
                }

                .report-table tbody tr:hover td {
                    background: white !important;
                }
            }

            .print-header {
                display: none;
            }
        </style>
    @endpush

    <div class="laporan-wrap">

        {{-- ═══ FILTER & EXPORT ═══ --}}
        <div class="filter-card print-hidden" wire:key="filter-section">
            <div style="display:grid; grid-template-columns: 1fr auto; gap: 32px; align-items: start;">

                {{-- Filter --}}
                <div wire:key="form-container">
                    <p class="filter-label">Filter Laporan</p>
                    {{ $this->form }}
                </div>

                {{-- Export --}}
                <div style="min-width: 180px;">
                    <p class="filter-label">Export</p>
                    <div style="display: flex; flex-direction: column; gap: 8px;">

                        <button type="button" class="btn-pdf" onclick="setTimeout(() => window.print(), 300)">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-2H7v2a2 2 0 002 2z" />
                            </svg>
                            Cetak PDF
                        </button>

                        <a href="{{ route('laporan-rapat.export-excel') }}" class="btn-excel"
                            onclick="document.getElementById('export-form').submit(); return false;">
                            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export Excel
                        </a>

                        <form id="export-form" action="{{ route('laporan-rapat.export-excel') }}" method="POST"
                            style="display:none;">
                            @csrf
                            <input type="hidden" name="period_type" value="">
                            <input type="hidden" name="month" value="">
                            <input type="hidden" name="quarter" value="">
                            <input type="hidden" name="year" value="">
                        </form>

                        <script>
                            document.getElementById('export-form').addEventListener('submit', function() {
                                const inputs = document.querySelectorAll('form:not(#export-form) input[name]');
                                inputs.forEach(input => {
                                    const target = document.querySelector('#export-form input[name="' + input.name + '"]');
                                    if (target) target.value = input.value;
                                });
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ PRINT HEADER ═══ --}}
        <div class="print-header"
            style="text-align:center; margin-bottom: 32px; padding-bottom: 20px; border-bottom: 2px solid #e9ecef;">
            <h1 style="font-size: 24px; font-weight: 800; color: #111827; margin: 0 0 4px;">
                {{ $reportData['churchName'] }}</h1>
            <h2 style="font-size: 15px; font-weight: 600; color: #6b7280; margin: 0 0 4px;">Laporan Rapat & Keuangan
            </h2>
            <p style="font-size: 13px; color: #9ca3af; margin: 0;">Periode: <strong
                    style="color:#374151;">{{ $reportData['periodLabel'] }}</strong></p>
        </div>

        {{-- ═══ LAPORAN PELAYANAN ═══ --}}
        <div class="card">
            <div class="section-header">
                <div class="section-header-icon" style="background:#eff6ff;">
                    <svg width="18" height="18" fill="none" stroke="#1d4ed8" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div>
                    <h2>Laporan Pelayanan & Kehadiran</h2>
                    <p>Data kegiatan dan kehadiran jemaat</p>
                </div>
            </div>

            @if ($reportData['events']->isEmpty())
                <div class="empty-state">
                    <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                    Tidak ada kegiatan pada periode ini
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="report-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kegiatan</th>
                                <th>Kategori</th>
                                <th>Pelayan</th>
                                <th class="center">♂ L</th>
                                <th class="center">♀ P</th>
                                <th class="center">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reportData['events'] as $event)
                                <tr>
                                    <td style="white-space:nowrap; color:#6b7280;">
                                        {{ $event->start_datetime->locale('id')->format('d M Y') }}
                                    </td>
                                    <td style="font-weight:600; color:#111827; min-width:160px;">
                                        {{ $event->title }}
                                    </td>
                                    <td>
                                        <span class="badge-cat">{{ $event->category->name ?? '-' }}</span>
                                    </td>
                                    <td style="min-width:160px;">
                                        @forelse ($event->rosters as $roster)
                                            <div class="roster-row">
                                                <span>{{ $roster->role->name ?? '-' }}:</span>
                                                @if ($roster->member_id)
                                                    {{ $roster->member->full_name ?? '-' }}
                                                @elseif ($roster->official_id)
                                                    {{ $roster->official->display_name ?? '-' }}
                                                @else
                                                    -
                                                @endif
                                            </div>
                                        @empty
                                            <span style="color:#d1d5db; font-size:12px;">—</span>
                                        @endforelse
                                    </td>
                                    <td class="td-center">
                                        <span class="attendance-num">{{ $event->attendance_male ?? 0 }}</span>
                                    </td>
                                    <td class="td-center">
                                        <span class="attendance-num">{{ $event->attendance_female ?? 0 }}</span>
                                    </td>
                                    <td class="td-center">
                                        <span class="attendance-total">{{ $event->total_attendance }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- ═══ LAPORAN KEUANGAN ═══ --}}
        <div class="card section-gap">
            <div class="section-header">
                <div class="section-header-icon" style="background:#f0fdf4;">
                    <svg width="18" height="18" fill="none" stroke="#15803d" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 01-.75.75h-.75M6.75 8.25h10.5" />
                    </svg>
                </div>
                <div>
                    <h2>Laporan Keuangan</h2>
                    <p>Ringkasan pemasukan, pengeluaran, dan saldo</p>
                </div>
            </div>

            {{-- Summary --}}
            <div class="fin-summary">
                <div class="fin-summary-item">
                    <p class="fin-label">Saldo Awal</p>
                    <p class="fin-amount blue">Rp {{ number_format($reportData['openingBalance'], 0, ',', '.') }}</p>
                </div>
                <div class="fin-summary-item">
                    <p class="fin-label">Pemasukan</p>
                    <p class="fin-amount green">Rp {{ number_format($reportData['totalIncome'], 0, ',', '.') }}</p>
                </div>
                <div class="fin-summary-item">
                    <p class="fin-label">Pengeluaran</p>
                    <p class="fin-amount red">Rp {{ number_format($reportData['totalExpenses'], 0, ',', '.') }}</p>
                </div>
            </div>

            {{-- Income & Expense tables --}}
            <div class="fin-tables">
                {{-- Income --}}
                <div class="fin-col">
                    <div class="fin-col-header green-h">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M4.5 10.5L12 3m0 0l7.5 7.5M12 3v18" />
                        </svg>
                        Pemasukan
                    </div>
                    @if ($reportData['income']->isEmpty())
                        <div class="empty-state" style="padding: 24px;">Tidak ada data</div>
                    @else
                        @foreach ($reportData['income'] as $item)
                            <div class="fin-row">
                                <span class="fin-row-cat">{{ $item['category'] }}</span>
                                <span class="fin-row-amt">Rp {{ number_format($item['total'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Expenses --}}
                <div class="fin-col">
                    <div class="fin-col-header red-h">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M19.5 13.5L12 21m0 0l-7.5-7.5M12 21V3" />
                        </svg>
                        Pengeluaran
                    </div>
                    @if ($reportData['expenses']->isEmpty())
                        <div class="empty-state" style="padding: 24px;">Tidak ada data</div>
                    @else
                        @foreach ($reportData['expenses'] as $item)
                            <div class="fin-row">
                                <span class="fin-row-cat">{{ $item['category'] }}</span>
                                <span class="fin-row-amt">Rp {{ number_format($item['total'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            {{-- Closing Balance --}}
            <div class="closing-bal">
                <p class="closing-label">Saldo Akhir</p>
                <p class="closing-amount {{ $reportData['closingBalance'] >= 0 ? 'surplus' : 'deficit' }}">
                    Rp {{ number_format($reportData['closingBalance'], 0, ',', '.') }}
                </p>
                <div>
                    <span class="closing-badge {{ $reportData['closingBalance'] >= 0 ? 'surplus' : 'deficit' }}">
                        @if ($reportData['closingBalance'] >= 0)
                            <svg width="12" height="12" fill="none" stroke="currentColor"
                                stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Surplus
                        @else
                            <svg width="12" height="12" fill="none" stroke="currentColor"
                                stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            Defisit
                        @endif
                    </span>
                </div>
            </div>
        </div>

    </div>{{-- end .laporan-wrap --}}

</x-filament-panels::page>
