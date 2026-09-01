<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BirthRecord;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Fase 3B T5 — Export Akta Lahir (PDF via dompdf, pola ReportExporter laporan).
 *
 * Guard server-side: hanya super_admin & church_admin (matriks lifecycle §7);
 * church_admin hanya untuk record gereja sendiri (isolasi tenant).
 */
class BirthRecordExportController extends Controller
{
    public function pdf(Request $request, BirthRecord $birthRecord)
    {
        $user = $request->user();

        abort_unless(
            in_array($user?->role, ['super_admin', 'church_admin'], true),
            403,
            'Tidak diizinkan mencetak akta lahir.'
        );

        if ($user->role !== 'super_admin' && (int) $birthRecord->church_id !== (int) $user->church_id) {
            abort(403, 'Akta lahir milik gereja lain.');
        }

        $birthRecord->loadMissing(['member.church']);

        $fileName = 'akta-lahir-' . Str::slug($birthRecord->member?->full_name ?? 'anggota') . '.pdf';

        return ReportExporter::pdf($fileName, view('pdf.akta-lahir', ['record' => $birthRecord]));
    }
}
