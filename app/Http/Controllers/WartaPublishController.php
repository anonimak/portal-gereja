<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Clusters\Reporting\Pages\WartaJemaat;
use App\Models\Church;
use App\Models\WartaPublication;
use App\Support\ChurchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Endpoint admin untuk mempublikasikan edisi Warta ke portal publik.
 *
 * Alur: admin membuka halaman Warta (data sudah tampil), kirim POST periode →
 * controller mengambil data via halaman yang sama (single source), lalu
 * menyimpan snapshot JSON sebagai WartaPublication (status=published).
 */
class WartaPublishController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        // RBAC: hanya penyusun Warta (super_admin/church_admin/warta_editor).
        abort_unless(Gate::forUser($user)->allows('create', WartaPublication::class), 403);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        // Gereja target: super_admin boleh pilih gereja lain (opsional); role lain
        // selalu gereja sendiri (isolasi tenant).
        $churchId = (int) ($validated['church_id'] ?? $user->church_id);
        if ($user->role !== 'super_admin' && (int) $user->church_id !== $churchId) {
            abort(403, 'Tidak diizinkan mempublikasikan untuk gereja lain.');
        }

        // Ambil data periode dari halaman yang sama (single source) dengan
        // konteks gereja yang benar untuk super_admin.
        if ($user->role === 'super_admin') {
            ChurchContext::setActiveChurch($churchId, $user);
        }

        try {
            $page = new WartaJemaat;
            $page->startDate = $startDate;
            $page->endDate = $endDate;
            $data = $page->getReportData();
        } finally {
            if ($user->role === 'super_admin') {
                ChurchContext::setActiveChurch(null, $user);
            }
        }

        $publication = WartaPublication::create([
            'church_id' => $churchId,
            'title' => $data['periodLabel'] ?? ('Warta '.$startDate->format('d-m-Y')),
            'period_start' => $startDate->toDateString(),
            'period_end' => $endDate->toDateString(),
            'content' => $this->snapshot($data),
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $user->id,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Warta berhasil dipublikasikan.',
                'publication' => [
                    'id' => $publication->id,
                    'title' => $publication->title,
                    'published_at' => $publication->published_at?->toIso8601String(),
                    'church_id' => $publication->church_id,
                    'url' => route('public.warta.show', [
                        'church' => Church::query()->withoutGlobalScopes()->find($churchId)?->code,
                        'publication' => $publication->id,
                    ]),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Warta berhasil dipublikasikan ke portal.');
    }

    /**
     * Ubah data laporan menjadi snapshot JSON ringan untuk portal publik.
     * Hanya data yang memang ingin ditampilkan ke jemaat (tanpa data internal).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function snapshot(array $data): array
    {
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
            'church_name' => $data['churchName'] ?? null,
            'church_address' => $data['churchAddress'] ?? null,
            'period_label' => $data['periodLabel'] ?? null,
            'edition_label' => $data['editionLabel'] ?? null,
            'events' => $events,
            'birthdays' => $birthdays,
            'sacraments' => $sacraments,
            'finance' => [
                'opening_balance' => $data['openingBalance'] ?? 0,
                'total_income' => $data['totalIncome'] ?? 0,
                'total_expenses' => $data['totalExpenses'] ?? 0,
                'closing_balance' => $data['closingBalance'] ?? 0,
            ],
        ];
    }
}
