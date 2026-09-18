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
            'reflection' => ['nullable', 'string'],
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
            $page->churchSelect = $churchId;
            $page->startDate = $startDate;
            $page->endDate = $endDate;
            if (array_key_exists('reflection', $validated)) {
                $page->reflection = $validated['reflection'];
            }
            $data = $page->getReportData();
            if (array_key_exists('reflection', $validated)) {
                $data['reflection'] = $validated['reflection'];
            }
        } finally {
            if ($user->role === 'super_admin') {
                ChurchContext::setActiveChurch(null, $user);
            }
        }

        $targetChurch = Church::query()->withoutGlobalScopes()->find($churchId);

        $publication = WartaPublication::withoutGlobalScopes()->updateOrCreate(
            [
                'church_id' => $churchId,
                'period_start' => $startDate->toDateString(),
                'period_end' => $endDate->toDateString(),
            ],
            [
                'title' => $data['periodLabel'] ?? ('Warta '.$startDate->format('d-m-Y')),
                'content' => $this->snapshot($data, $targetChurch),
                'status' => 'published',
                'published_at' => now(),
                'created_by' => $user->id,
            ]
        );

        if ($publication->trashed()) {
            $publication->restore();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Warta berhasil dipublikasikan.',
                'publication' => [
                    'id' => $publication->id,
                    'title' => $publication->title,
                    'published_at' => $publication->published_at?->toIso8601String(),
                    'church_id' => $publication->church_id,
                    'url' => route('public.warta.show', [
                        'church' => $targetChurch?->code,
                        'publication' => $publication->id,
                    ]),
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Warta berhasil dipublikasikan ke portal.');
    }

    public function publish(Request $request)
    {
        return $this->__invoke($request);
    }

    /**
     * Endpoint untuk membatalkan / menarik publikasi warta (rollback).
     */
    public function rollback(Request $request)
    {
        $user = $request->user();

        // RBAC: hanya super_admin/church_admin/warta_editor (Gate allows delete on WartaPublication)
        abort_unless(Gate::forUser($user)->allows('delete', WartaPublication::class), 403);

        $validated = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'church_id' => ['nullable', 'integer', 'exists:churches,id'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);

        $churchId = (int) ($validated['church_id'] ?? $user->church_id);
        if ($user->role !== 'super_admin' && (int) $user->church_id !== $churchId) {
            abort(403, 'Tidak diizinkan menarik publikasi untuk gereja lain.');
        }

        $publication = WartaPublication::withoutGlobalScopes()
            ->where('church_id', $churchId)
            ->whereDate('period_start', $startDate->toDateString())
            ->whereDate('period_end', $endDate->toDateString())
            ->whereNull('deleted_at')
            ->first();

        if (! $publication) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Publikasi warta tidak ditemukan atau sudah ditarik.',
                ], 404);
            }

            return redirect()->back()->with('warning', 'Publikasi warta tidak ditemukan atau sudah ditarik.');
        }

        abort_unless(Gate::forUser($user)->allows('delete', $publication), 403);

        $publication->update([
            'status' => 'draft',
            'published_at' => null,
        ]);
        $publication->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Publikasi warta berhasil ditarik (rollback).',
                'publication' => [
                    'id' => $publication->id,
                    'status' => $publication->status,
                    'church_id' => $publication->church_id,
                ],
            ]);
        }

        return redirect()->back()->with('success', 'Publikasi warta berhasil ditarik (rollback).');
    }

    /**
     * Ubah data laporan menjadi snapshot JSON ringan untuk portal publik.
     * Hanya data yang memang ingin ditampilkan ke jemaat (tanpa data internal).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function snapshot(array $data, ?Church $church = null): array
    {
        $church ??= ($data['church'] ?? null);

        $events = collect($data['events'] ?? [])->map(fn ($event) => [
            'name' => $event->name ?? $event->title ?? 'Ibadah',
            'start' => optional($event->start_datetime)->format('d/m/Y H:i'),
            'location' => $event->location ?? '',
            'officials' => collect($event->rosters ?? [])
                ->map(fn ($r) => $r->member?->full_name ?? $r->official?->display_name)
                ->filter()
                ->implode(', '),
        ])->all();

        $pastEvents = collect($data['pastEvents'] ?? $data['past_events'] ?? [])->map(function ($event) {
            if (is_array($event)) {
                return [
                    'name' => $event['name'] ?? $event['title'] ?? 'Kegiatan',
                    'start' => $event['start'] ?? '',
                    'location' => $event['location'] ?? '',
                    'officials' => $event['officials'] ?? '',
                    'total_attendance' => (int) ($event['total_attendance'] ?? 0),
                    'attendance_male' => (int) ($event['attendance_male'] ?? 0),
                    'attendance_female' => (int) ($event['attendance_female'] ?? 0),
                ];
            }

            return [
                'name' => $event->name ?? $event->title ?? 'Kegiatan',
                'start' => optional($event->start_datetime)->format('d/m/Y H:i'),
                'location' => $event->location ?? '',
                'officials' => collect($event->rosters ?? [])
                    ->map(fn ($r) => $r->member?->full_name ?? $r->official?->display_name)
                    ->filter()
                    ->implode(', '),
                'total_attendance' => (int) ($event->total_attendance ?? 0),
                'attendance_male' => (int) ($event->attendance_male ?? 0),
                'attendance_female' => (int) ($event->attendance_female ?? 0),
            ];
        })->all();

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
            'reflection' => $data['reflection'] ?? null,
            'renungan' => $data['reflection'] ?? null,
            'events' => $events,
            'past_events' => $pastEvents,
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
}
