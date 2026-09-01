<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MemberSacrament;
use App\Services\ReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Endpoint penerbitan Dokumen Baptis Anak (Fase 3B T6).
 *
 * GET /admin/sakramen/baptis-anak/{sacrament}/export-pdf
 * Guard: role panel (super_admin/church_admin) + isolasi tenant (global scope
 * BelongsToChurch) + policy MemberSacrament.
 */
class BaptisAnakExportController extends Controller
{
    public function pdf(Request $request, int $sacrament)
    {
        $user = $request->user();

        // Role guard — finance_admin & role non-panel ditolak (spec §7 lifecycle).
        abort_unless(
            in_array($user?->role, ['super_admin', 'church_admin'], true),
            403,
            'Tidak diizinkan menerbitkan dokumen baptis anak.'
        );

        // Pakai withoutGlobalScopes supaya record gereja lain tetap ketemu lalu
        // ditolak eksplisit 403 (AC-LC-13: URL langsung record gereja lain -> 403),
        // bukan 404 dari scope global.
        $record = MemberSacrament::query()
            ->withoutGlobalScopes()
            ->with(['member.family', 'official'])
            ->findOrFail($sacrament);

        if ($record->type !== 'baptis_anak') {
            abort(422, 'Sakramen ini bukan Baptis Anak.');
        }

        if ($user->role !== 'super_admin' && $record->church_id !== $user->church_id) {
            abort(403, 'Data gereja lain.');
        }

        $member = $record->member;
        $family = $member?->family;
        $father = $family?->members
            ?->first(fn ($m) => $m->family_relation === 'kepala_keluarga');
        $mother = $family?->members
            ?->first(fn ($m) => $m->family_relation === 'istri');

        $data = [
            'churchName' => $record->church?->name,
            'churchAddress' => $record->church?->address,
            'churchLocation' => $record->church?->address,
            'certificateNumber' => $record->certificate_number,
            'childName' => $member?->full_name,
            'gender' => match ($member?->gender) {
                'L' => 'Laki-laki',
                'P' => 'Perempuan',
                default => $member?->gender,
            },
            'birthPlace' => $member?->birth_place,
            'birthDate' => $member?->birth_date?->format('d M Y'),
            'fatherName' => $father?->full_name,
            'motherName' => $mother?->full_name,
            'baptismDate' => $record->sacrament_date?->format('d M Y'),
            'issuedAt' => $record->issued_at?->format('d M Y'),
            'ministerName' => $record->official?->display_name,
        ];

        $view = View::make('pdf.dokumen-baptis-anak', $data);

        return ReportExporter::pdf('dokumen-baptis-anak-'.$record->id.'.pdf', $view, $data);
    }
}
