<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DeathRecord;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Endpoint penerbitan Surat Keterangan Kematian (Fase 3B T11).
 *
 * GET /admin/death-record/{deathRecord}/export-pdf
 * Guard: role panel (super_admin/church_admin) + isolasi tenant
 * (nonaktifkan scope church saja, pertahankan SoftDeletingScope -> 404
 * untuk record yang sudah di-soft-delete) — pola MarriageExportController.
 */
class DeathRecordExportController extends Controller
{
    public function pdf(Request $request, int $deathRecord)
    {
        $user = $request->user();

        abort_unless(
            in_array($user?->role, ['super_admin', 'church_admin'], true),
            403,
            'Tidak diizinkan menerbitkan Surat Keterangan Kematian.'
        );

        $record = DeathRecord::query()
            ->withoutGlobalScope('church')
            ->with(['member.church', 'official'])
            ->findOrFail($deathRecord);

        if ($user->role !== 'super_admin' && $record->church_id !== $user->church_id) {
            abort(403, 'Data gereja lain.');
        }

        $data = [
            'churchName' => $record->church?->name,
            'churchAddress' => $record->church?->address,
            'certificateNumber' => $record->certificate_number,
            'memberName' => $record->member?->full_name,
            'memberGender' => $record->member?->gender === 'f' ? 'Perempuan' : 'Laki-laki',
            'memberBirthPlace' => $record->member?->birth_place,
            'memberBirthDate' => $record->member?->birth_date?->format('d M Y'),
            'memberAddress' => $record->member?->family?->address,
            'deathDate' => $record->death_date?->format('d M Y'),
            'burialDate' => $record->burial_date?->format('d M Y'),
            'burialLocation' => $record->burial_location,
            'ministerName' => $record->official?->display_name,
            'issuedAt' => $record->issued_at?->format('d M Y'),
        ];

        $view = View::make('pdf.surat-kematian', $data);

        return ReportExporter::pdf('surat-kematian-' . $record->id . '.pdf', $view, $data);
    }
}
