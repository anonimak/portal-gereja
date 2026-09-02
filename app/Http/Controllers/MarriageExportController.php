<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Marriage;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Endpoint penerbitan Akta Nikah (Fase 3B T9).
 *
 * GET /admin/marriage/{marriage}/export-pdf
 * Guard: role panel (super_admin/church_admin) + isolasi tenant
 * (nonaktifkan scope church saja, pertahankan SoftDeletingScope -> 404
 * untuk record yang sudah di-soft-delete) — pola BaptisAnakExportController.
 */
class MarriageExportController extends Controller
{
    public function pdf(Request $request, int $marriage)
    {
        $user = $request->user();

        // Role guard — finance_admin & role non-panel ditolak (spec §7 lifecycle).
        abort_unless(
            in_array($user?->role, ['super_admin', 'church_admin'], true),
            403,
            'Tidak diizinkan menerbitkan Akta Nikah.'
        );

        $record = Marriage::query()
            ->withoutGlobalScope('church')
            ->with(['husband', 'wife', 'official', 'program'])
            ->findOrFail($marriage);

        if ($user->role !== 'super_admin' && $record->church_id !== $user->church_id) {
            abort(403, 'Data gereja lain.');
        }

        $husband = $record->husband;
        $wife = $record->wife;

        $data = [
            'churchName' => $record->church?->name,
            'churchAddress' => $record->church?->address,
            'churchLocation' => $record->church?->address,
            'certificateNumber' => $record->certificate_number,
            'husbandName' => $husband?->full_name,
            'wifeName' => $wife?->full_name,
            'marriageDate' => $record->marriage_date?->format('d M Y'),
            'location' => $record->location,
            'witnessNames' => $record->witness_names,
            'ministerName' => $record->official?->display_name,
            'issuedAt' => $record->issued_at?->format('d M Y'),
        ];

        $view = View::make('pdf.akta-nikah', $data);

        return ReportExporter::pdf('akta-nikah-'.$record->id.'.pdf', $view, $data);
    }
}
