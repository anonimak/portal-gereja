<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Church;
use App\Models\WartaPublication;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Portal publik Warta Jemaat (tanpa login) — satu gereja per halaman.
 *
 * Hanya menampilkan edisi status=published & published_at <= now milik gereja
 * yang dipilih (route by church code). Konten berasal dari snapshot JSON —
 * bukan query live — sehingga tidak membocorkan data gereja lain.
 */
class PublicWartaController extends Controller
{
    /**
     * Halaman pilih/lihat gereja: /warta (default gereja pertama yg punya edisi)
     * atau /warta/{church:code}.
     */
    public function index(Request $request, ?string $churchCode = null): View
    {
        $church = $this->resolveChurch($churchCode);

        $publications = WartaPublication::query()
            ->forChurch($church->id)
            ->published()
            ->latest('published_at')
            ->get();

        $churches = Church::query()
            ->withoutGlobalScopes()
            ->whereHas('wartaPublications', fn ($q) => $q->published())
            ->orderBy('name')
            ->get();

        return view('public.warta.index', [
            'church' => $church,
            'publications' => $publications,
            'churches' => $churches,
        ]);
    }

    /**
     * Detail satu edisi: /warta/{church:code}/{publication}.
     */
    public function show(string $churchCode, WartaPublication $publication): View
    {
        $church = $this->resolveChurch($churchCode);

        // Amankan: hanya edisi gereja tsb & published (404 untuk lainnya).
        if ((int) $publication->church_id !== (int) $church->id
            || $publication->status !== 'published'
            || $publication->published_at === null
            || $publication->published_at->isFuture()) {
            abort(404);
        }

        return view('public.warta.show', [
            'church' => $church,
            'publication' => $publication,
            'content' => $publication->content ?? [],
        ]);
    }

    private function resolveChurch(?string $code): Church
    {
        $query = Church::query()->withoutGlobalScopes();

        if ($code) {
            $church = $query->where('code', $code)->first();
            if ($church) {
                return $church;
            }
        }

        // Fallback: gereja pertama yang punya edisi published; kalau tidak ada,
        // gereja pertama di DB (supaya halaman tetap bisa dibuka utk demo).
        $church = Church::query()
            ->withoutGlobalScopes()
            ->whereHas('wartaPublications', fn ($q) => $q->published())
            ->orderBy('name')
            ->first();

        if ($church) {
            return $church;
        }

        $first = Church::query()->withoutGlobalScopes()->orderBy('name')->first();
        if ($first) {
            return $first;
        }

        throw new ModelNotFoundException('Belum ada gereja terdaftar.');
    }
}
